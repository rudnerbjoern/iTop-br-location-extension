<?php

declare(strict_types=1);

// Run the actual XML methods with only the iTop services they need substituted.
final class TestEventData
{
    public function __construct(private bool $isNew = false) {}
    public function Get(string $key): bool { return $this->isNew; }
}
class_alias(TestEventData::class, 'Combodo\\iTop\\Service\\Events\\EventData');
define('OPT_ATT_HIDDEN', 1);
define('OPT_ATT_READONLY', 2);

final class MetaModel
{
    public static array $locations = [];
    public static function GetObject(string $class, int $id): object
    {
        return self::$locations[$id];
    }
}

final class TestChildren
{
    private int $position = 0;
    public function __construct(private array $children = []) {}
    public function Fetch(): ?object { return $this->children[$this->position++] ?? null; }
}

$model = simplexml_load_file(__DIR__ . '/../br-location-extension/datamodel.br-location-extension.xml', SimpleXMLElement::class, LIBXML_NONET);
if ($model === false) {
    throw new RuntimeException('Cannot load the Location data model.');
}
$methods = $model->xpath('/itop_design/classes/class[@id="Location"]/methods/method/code');
if ($methods === false || count($methods) !== 6) {
    throw new RuntimeException('Expected six Location methods.');
}
eval('trait LocationMethodsUnderTest {' . implode("\n", array_map('strval', $methods)) . '}');

final class TestLocation
{
    use LocationMethodsUnderTest;

    public array $changes = [];
    public array $flags = [];
    public array $initialFlags = [];
    public int $writes = 0;
    public bool $i_NameChanged;
    private array $values;

    public function __construct(string $name, int $parent = 0, array $children = [])
    {
        $this->values = ['name' => $name, 'parent_id' => $parent, 'nicename' => 'stale',
            'locations_list' => new TestChildren($children)];
    }
    public function Get(string $key): mixed { return $this->values[$key]; }
    public function Set(string $key, mixed $value): void { $this->values[$key] = $value; }
    public function ListChanges(): array { return $this->changes; }
    public function ForceInitialAttributeFlags(string $key, int $flags): void { $this->initialFlags[$key] = $flags; }
    public function ForceAttributeFlags(string $key, int $flags): void { $this->flags[$key] = $flags; }
    public function DBUpdate(): void
    {
        $this->writes++;
        $this->OnLocationAfterWriteNicename(new TestEventData());
    }
}

function expectSame(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

$tests = [
    'root path preserves the editable name' => static function (): void {
        $location = new TestLocation('Campus');
        $location->SetNicename();
        expectSame('Campus', $location->Get('nicename'));
        expectSame('Campus', $location->Get('name'));
    },
    'child path uses the complete parent path' => static function (): void {
        $parent = new TestLocation('Floor');
        $parent->Set('nicename', 'Campus/Building/Floor');
        MetaModel::$locations = [1 => $parent];
        $location = new TestLocation('Room 301', 1);
        $location->SetNicename();
        expectSame('Campus/Building/Floor/Room 301', $location->Get('nicename'));
    },
    'removing the parent clears the old path prefix' => static function (): void {
        $location = new TestLocation('Room');
        $location->Set('nicename', 'Campus/Building/Room');
        $location->changes = ['parent_id' => true];
        $location->OnLocationBeforeWriteNicename(new TestEventData());
        expectSame('Room', $location->Get('nicename'));
    },
    'new locations compute their path before writing' => static function (): void {
        $location = new TestLocation('Campus');
        $location->OnLocationBeforeWriteNicename(new TestEventData(true));
        expectSame('Campus', $location->Get('nicename'));
    },
    'unrelated changes leave the path and children untouched' => static function (): void {
        $child = new TestLocation('Room');
        $location = new TestLocation('Campus', 0, [$child]);
        $location->changes = ['description' => 'Updated'];
        $location->OnLocationBeforeWriteNicename(new TestEventData());
        $location->OnLocationAfterWriteNicename(new TestEventData());
        expectSame('stale', $location->Get('nicename'));
        expectSame(0, $child->writes);
    },
    'nicename is hidden initially and read-only afterwards' => static function (): void {
        $location = new TestLocation('Campus');
        $location->OnLocationSetInitialAttributesFlagsNicename(new TestEventData());
        $location->OnLocationSetAttributesFlagsNicename(new TestEventData());
        expectSame(['nicename' => OPT_ATT_HIDDEN], $location->initialFlags);
        expectSame(['nicename' => OPT_ATT_READONLY], $location->flags);
    },
    'bridge removes the alternate type field from the model and all views' => static function (): void {
        $bridge = simplexml_load_file(__DIR__ . '/../br-location-extension-bridge-for-molkobain-datacenter-view/datamodel.br-location-extension-bridge-for-molkobain-datacenter-view.xml', SimpleXMLElement::class, LIBXML_NONET);
        if ($bridge === false) {
            throw new RuntimeException('Cannot load the bridge data model.');
        }
        foreach (['fields/field', 'presentation/details/items/item', 'presentation/default_search/items/item', 'presentation/list/items/item'] as $path) {
            $nodes = $bridge->xpath('/itop_design/classes/class[@id="Location"]/' . $path . '[@id="locationtype_id"]');
            expectSame(1, count($nodes));
            expectSame('delete_if_exists', (string) $nodes[0]['_delta']);
        }
        $nodes = $bridge->xpath('/itop_design/menus/menu[@id="Typology"]/definition/cells/cell/dashlets/dashlet[@id="LocationType"]');
        expectSame(1, count($nodes));
        expectSame('delete_if_exists', (string) $nodes[0]['_delta']);
    },
];

foreach (['name', 'parent_id'] as $change) {
    $tests["$change changes cascade through descendants and siblings"] = static function () use ($change): void {
        $room = new TestLocation('Room', 2);
        $floor = new TestLocation('Floor', 1, [$room]);
        $sibling = new TestLocation('Other floor', 1);
        $location = new TestLocation('Building', 0, [$floor, $sibling]);
        $parent = new TestLocation('Campus');
        $parent->SetNicename();
        MetaModel::$locations = [1 => $location, 2 => $floor, 3 => $parent];
        $location->Set('nicename', 'Old building');
        $location->Set($change, $change === 'name' ? 'New building' : 3);
        $location->changes = [$change => true];
        $location->OnLocationBeforeWriteNicename(new TestEventData());
        $location->OnLocationAfterWriteNicename(new TestEventData());
        $prefix = $change === 'name' ? 'New building' : 'Campus/Building';
        expectSame($prefix, $location->Get('nicename'));
        expectSame($prefix . '/Floor', $floor->Get('nicename'));
        expectSame($prefix . '/Floor/Room', $room->Get('nicename'));
        expectSame($prefix . '/Other floor', $sibling->Get('nicename'));
        foreach ([$floor, $room, $sibling] as $child) {
            expectSame(true, $child->writes > 0);
        }
    };
}

$failures = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        echo "PASS: $name\n";
    } catch (Throwable $error) {
        $failures++;
        fwrite(STDERR, "FAIL: $name: {$error->getMessage()}\n");
    }
}
echo count($tests) . " tests, $failures failures\n";
exit($failures === 0 ? 0 : 1);
