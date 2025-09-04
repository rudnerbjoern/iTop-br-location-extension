# iTop Location Nicename (Hierarchical Paths for Locations)

Copyright (c) 2021-2025 Björn Rudner
[![License](https://img.shields.io/github/license/rudnerbjoern/iTop-br-location-extension)](https://github.com/rudnerbjoern/iTop-br-location-extension/blob/main/LICENSE)

## What?

A lightweight iTop extension that introduces a computed **`nicename`** attribute for the `Location` class and makes it the display name of locations. The `nicename` is built as a **slash‑separated path** combining a location’s own `name` with its ancestors (e.g. `HQ/Building A/Floor 3/Room 301`).

> **Why?**
> iTop’s default `Location` name is flat. In large infrastructures, hierarchical context (site → building → floor → room …) improves readability, search, and reporting. This extension keeps your editable `name` intact while providing a read‑only, auto‑managed `nicename` that reflects the full path.

## Key Features

* **Computed display name**: The object naming is switched to `nicename`; users still edit the standard `name` field.
* **Auto‑path build**: `nicename = parent.nicename + '/' + name` (or just `name` if there is no parent).
* **Read‑only & hidden**: `nicename` is hidden during initial flag computation and then enforced read‑only; users cannot modify it directly.
* **Cascading updates**: Renaming or re‑parenting a location recalculates its `nicename` and **recursively updates all children**.
* **Hierarchy constraints**: Parent selection is limited to locations from the **same organization** with a **lower `type` level**.
* **Presentation tweaks**: Adds `description`, adjusts field order in details/list/search views for better UX.

## Data Model Overview

This extension modifies the built‑in `Location` class.

### New / Modified Attributes

* **`nicename`** (`AttributeString`, not null)
  * Default: `"unknown"` (replaced at save time by the computed path)
  * Hidden initially; enforced **read‑only** afterwards
  * Used as the **naming attribute** of `Location`
* **`type`** (`AttributeEnum`)
  * Numeric values **`1`..`5`** defined (you should label these in your dictionaries; see below)
* **`parent_id`** (`AttributeHierarchicalKey`)
  * Filter: only parents with **`parent.type < this.type`** and **same `org_id`**
  * On target delete: `DEL_AUTO`
* **`locations_list`** (`AttributeLinkedSet`) – children collection
* **`description`** (`AttributeText`) – optional free text

### Event Listeners & Methods

* `EvtSetInitialNicenameAttributeFlags` → hides `nicename` during initial flag computation
* `EvtSetNicenameAttributeFlags` → enforces `nicename` as read‑only
* `EvtBeforeNicenameWrite` → computes `nicename` on create, and on changes to `name` or `parent_id`
* `EvtAfterNicenameWrite` → if `name`/parent changed, calls `UpdateChildren()` to cascade
* `SetNicename()` → computes path using parent’s `nicename`
* `UpdateChildren()` → depth‑first update of descendants (calls `DBUpdate()` for each child)

> **Performance note:** On very large trees, bulk renames/re‑parents will trigger recursive updates; consider planning such changes during maintenance windows.

## Compatibility

* **iTop schema version:** `3.2` (extension file references `itop_design.xsd` 3.2)
* **iTop core:** tested with iTop **3.2.2**

> If you run an older iTop, upgrade to 3.2+ before installing.

The branch [2.7](https://github.com/rudnerbjoern/iTop-br-location-extension/tree/itop/2.7) is compatible to iTop 2.7 and iTop 3.1, but is not maintained any longer.

The branch [main](https://github.com/rudnerbjoern/iTop-br-location-extension/tree/main) will only be compatible to iTop 3.2 and above.

Versions starting with 2.7.x are kept compatible to iTop 2.7

## Installation

1. Copy the extension folder into your iTop instance under `extensions/`.
2. Clear the iTop cache if needed.
3. Run the iTop **setup** (web installer or CLI) to apply the data model changes.
4. Log in and verify that `Location` objects now display the computed `nicename`.

### Dictionary Labels

Define labels for the `type` enumeration and any UI captions you need. Example (PHP dictionary):

```php
<?php
Dict::Add('EN US', 'English', 'English', [
    'Class:Location/Attribute:type/Value:1' => 'Campus',
    'Class:Location/Attribute:type/Value:2' => 'Building',
    'Class:Location/Attribute:type/Value:3' => 'Floor',
    'Class:Location/Attribute:type/Value:4' => 'Room',
    'Class:Location/Attribute:type/Value:5' => 'Tile',
]);
```

> Adjust the labels and levels to match your organization. The **parent filter relies on numeric ordering** (`parent.type < child.type`), so ensure lower numbers mean higher/outer levels.

## Usage

* Create or edit a `Location` as usual, setting **`name`**, **`type`**, and **`parent`**.
* On save, the extension computes **`nicename`**. Example:
  * `Campus = "HQ"` → `nicename = "HQ"`
  * `Building A` under `HQ` → `nicename = "HQ/Building A"`
  * `Floor 3` under `Building A` → `nicename = "HQ/Building A/Floor 3"`
* If you later rename `Building A` to `Main Building` or move `Floor 3` to another building, the path **recomputes automatically** and cascades to all descendants.

## Configuration & Extensibility

* **Organizations:** Parent and child must share the same `org_id`. Cross‑org location trees are intentionally blocked by the filter.
* **Custom levels:** You can extend the `type` enum with more values and dictionary labels; keep ordering consistent with your hierarchy.
* **Presentation:** Field ranks are tuned for details/list/search screens; adjust if needed via a small delta XML in your own extension.

## Known Limitations

* The extension does **not** alter the editable `name` field; it only changes **what is displayed** as the object title.
* Large, deep hierarchies may take longer to update when renaming/re‑parenting high‑level nodes.
* Cross‑organization parenting is not supported by design.

## Development Notes

This repository includes the XML datamodel with:

* `naming` switched to `nicename` (previous `name` removed from naming attributes)
* event listeners implementing the behavior described above
* recursive child updates via `UpdateChildren()`

Feel free to open issues or pull requests for improvements (e.g., additional levels, bulk operations, or UI enhancements).
