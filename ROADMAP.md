# Electricity Billing System — Development Roadmap

A phased, step-by-step build plan for the electricity customer billing & management
system.

## Stack

- **Framework:** Laravel 11
- **UI:** Blade + Bootstrap
- **Conventions:** resource controllers, soft deletes, `status` flag,
  `created_by` / `updated_by` audit columns

## Current Status

| Module | Feature | Status |
| ------ | ------- | ------ |
| — | Auth / Login | ✅ Done |
| — | User Management | ✅ Done |
| 2 | Customer Management (photo, name, mobile, address, NID, meter no, connection type, activate/deactivate, search) | ✅ Done |
| — | Sheets + Customer↔Sheet relation | ✅ Done |
| 3 | Meter Reading | ⬜ To do |
| 4 | Billing | ⬜ To do |
| 5 | Bill Printing | ⬜ To do |
| 6 | Payment Collection | ⬜ To do |
| 7 | Outstanding Balance | ⬜ To do |
| 8 | Reports | ⬜ To do |
| 9 | Expense Management | ⬜ To do |

## Build Order Principle

Build bottom-up — each layer depends on the one before it:

```
Settings/Tariff → Meter Reading → Billing → Payments → Outstanding / Reports
Expenses → Profit & Loss
```

---

## Phase 0 — Settings / Tariff Foundation

> Do first. Billing needs configurable rates. Never hardcode them.

1. Migration `tariffs` table: `per_unit_rate`, `fixed_charge`, `meter_rent`,
   `late_fee_type`, `late_fee_amount`, `effective_from`, `status`, audit + soft deletes.
   - Prefer a `tariffs` table over a flat key/value `settings` table so historical
     rates are preserved for old bills.
2. `Tariff` model + controller + admin-only settings form.

## Phase 1 — Meter Reading (Module 3)

1. Migration `meter_readings`: `customer_id`, `previous_reading`, `current_reading`,
   `consumed_units`, `reading_date`, `reader_name`, `photo`, `status`, audit + soft deletes.
2. `MeterReading` model — `belongsTo(Customer)`; `Customer hasMany(readings)`.
3. Controller:
   - Auto-pull `previous_reading` from the customer's last reading.
   - Auto-calc `consumed_units = current − previous`.
   - Validate `current_reading ≥ previous_reading`.
4. Views: index (filter by sheet / customer / month), create & edit form with photo
   upload, per-customer reading history.

## Phase 2 — Billing (Module 4)

1. Migration `bills`: `customer_id`, `meter_reading_id`, `billing_month`, `units`,
   `per_unit_rate`, `energy_charge`, `fixed_charge`, `meter_rent`,
   `previous_outstanding`, `late_fee`, `total_amount`, `paid_amount`, `due_amount`,
   `status` (unpaid / partial / paid), audit + soft deletes.
2. `Bill` model + a `BillCalculator` service so total logic lives in one place.
3. **One-click monthly generation** — action / artisan command that:
   - Loops active customers with a reading for the month.
   - Pulls the current tariff.
   - Rolls in previous outstanding balance.
   - Skips customers already billed for that month.
4. Views: bill index (filter by month / sheet / status), single bill view.

## Phase 3 — Bill Printing (Module 5)

1. Packages: `barryvdh/laravel-dompdf` (PDF), `simplesoftwareio/simple-qrcode` (QR).
2. A4 print view + receipt/thermal-size view.
3. PDF download route.
4. QR code encoding a bill-verification URL.

## Phase 4 — Payment Collection (Module 6)

1. Migration `payments`: `bill_id`, `customer_id`, `amount`, `payment_date`,
   `collector_id` (user), `method`, `status`, audit.
2. On store — support full or partial payment; update the bill's `paid_amount` /
   `due_amount` / `status`; recompute customer outstanding.
3. Views: payment form, payment receipt (PDF), payment history per customer.

## Phase 5 — Outstanding Balance (Module 7)

Read-only aggregation over bills & payments:

- Customers-with-dues list.
- Outstanding-months count + total outstanding amount.
- Disconnection list (e.g. dues ≥ N months).
- Outstanding balance report.

## Phase 6 — Expense Management (Module 9)

1. Migration `expense_categories` (Line Maintenance, Transformer, Salaries, Other)
   + `expenses` (`category_id`, `amount`, `date`, `note`, audit).
2. CRUD.
3. Profit & Loss = collections − expenses.

## Phase 7 — Reports (Module 8)

> Build last — needs data from every module.

- Daily collection report
- Monthly collection report
- Customer report
- Unit consumption report
- Outstanding balance report
- Income & expense report

Implemented as filtered query pages with PDF / Excel export.

---

## Recommended Next Step

Start with **Phase 0 (Tariff/Settings)** then **Phase 1 (Meter Reading)** — everything
downstream depends on rates and readings.
