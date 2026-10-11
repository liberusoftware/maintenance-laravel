# Maintenance Domain Architecture

## Overview

The maintenance domain manages physical assets, work orders, preventive schedules, and service history. It supports reactive, preventive, and condition-based maintenance strategies across multiple sites and asset hierarchies.

## Asset Register

Assets are the core entities representing equipment, machinery, or infrastructure requiring maintenance.

| Field | Description |
|-------|-------------|
| `asset_id` | Unique identifier (UUID) |
| `name` | Human-readable asset name |
| `category_id` | Reference to asset category |
| `parent_id` | Hierarchical parent asset (nullable) |
| `location_id` | Physical location reference |
| `serial_number` | Manufacturer serial number |
| `status` | `operational`, `degraded`, `down`, `retired` |
| `criticality` | `low`, `medium`, `high`, `critical` |
| `commission_date` | Date asset entered service |
| `warranty_expiry` | Warranty coverage end date |

### Categories and Hierarchies

- Categories define asset types (e.g., HVAC, Electrical, Mechanical)
- Assets support parent-child relationships for sub-assemblies
- Attributes are stored as JSON for flexible schema per category
- Locations form their own hierarchy (site → building → floor → room)

## Preventive Maintenance

Schedules define recurring maintenance tasks based on time or usage triggers.

```php
// Schedule trigger types
enum TriggerType {
    case TIME_BASED;        // Every N days/weeks/months
    case USAGE_BASED;       // Every N operating hours/cycles
    case CONDITION_BASED;   // Sensor threshold exceeded
    case EVENT_BASED;       // Triggered by another work order
}
```

### Checklists

- Each schedule has an ordered checklist of tasks
- Checklist items can require sign-off, measurements, or photo evidence
- Overdue schedules escalate based on grace period configuration

## Work Orders

Work orders track maintenance tasks from creation to completion.

### Lifecycle

```
draft → open → assigned → in_progress → on_hold → completed → verified
```

### Prioritization

| Priority | Response Time | Resolution Target |
|----------|--------------|-------------------|
| Emergency | 1 hour | 4 hours |
| High | 4 hours | 24 hours |
| Medium | 24 hours | 72 hours |
| Low | 72 hours | 1 week |

### Assignment

- Manual assignment by dispatcher
- Auto-assignment based on technician skills, availability, and workload
- Escalation rules when SLAs are at risk

## Service History

Every completed work order generates a service history record.

- **Logs**: Timestamped entries of work performed
- **Costs**: Labor, parts, and external service costs
- **Downtime**: Duration asset was unavailable
- **Warranties**: Linked warranty claims and coverage status

## Technician Management

| Entity | Purpose |
|--------|---------|
| `technician` | Worker profile with skills and certifications |
| `skill` | Named competency with proficiency level |
| `availability` | Working hours and time-off calendar |
| `assignment` | Work order allocation with scheduled hours |

- Skills are versioned and can expire (certifications)
- Availability considers shifts, holidays, and existing assignments

## Parts and Inventory

- Stock levels tracked per warehouse/location
- Reorder points trigger purchase requests when stock falls below threshold
- Parts linked to assets via bill of materials (BOM)
- Suppliers with lead times and pricing agreements

## Maintenance Strategies

| Strategy | Description |
|----------|-------------|
| Reactive | Fix on failure; minimal planning |
| Preventive | Time/usage-based scheduled maintenance |
| Predictive | ML/condition-based failure prediction |
| Condition-based | Sensor-triggered maintenance |

## Reporting and KPIs

| KPI | Formula |
|-----|---------|
| MTBF | Total operating time / Number of failures |
| MTTR | Total downtime / Number of repairs |
| Availability | (Uptime / Total time) × 100 |
| Maintenance cost | Labor + Parts + External per period |
| Schedule compliance | On-time completions / Total scheduled |

## Module Boundaries and Extension Points

| Module | Extension Point |
|--------|---------------|
| `Assets` | Custom attribute schemas, category-specific logic |
| `Schedules` | Custom trigger types, notification channels |
| `WorkOrders` | Custom statuses, approval workflows |
| `Inventory` | Custom reorder strategies, supplier integrations |
| `Reporting` | Custom KPI definitions, export formats |

- All modules expose Laravel Events for external integration
- Service providers allow binding custom implementations
- Policies enforce authorization at module boundaries
