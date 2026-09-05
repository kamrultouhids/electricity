<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for the access paths the list screens and reports actually use.
     *
     * The per-customer paths were already covered; what was missing is every
     * query that has no customer to anchor on — status filters, date-range
     * report sweeps, and the "latest bill per customer" subquery that the due
     * list, outstanding report, customer report and dashboard all depend on.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Both customer-facing reports order the whole table by serial_no.
            $table->index('serial_no');
            // The most-used filter pair on the customer list and every report.
            $table->index(['status', 'sheet_id']);
            // Meter Not Read and its dashboard tile sweep on this alone.
            $table->index('connection_date');
        });

        Schema::table('meter_readings', function (Blueprint $table) {
            // Pending readings: the billing queue, the bulk-generate screen,
            // the dashboard tile and the badge on the bill list.
            $table->index(['status', 'reading_date']);
        });

        Schema::table('bills', function (Blueprint $table) {
            // "Latest bill per customer" — MAX(billing_month) grouped by
            // customer, and the correlated newest-first subselect whose
            // tiebreak is id. Scanned backwards for the DESC ordering, so a
            // plain ascending index serves both.
            $table->index(['customer_id', 'billing_month', 'id'], 'bills_customer_month_id_index');
            // Superseded: a strict prefix of the index above.
            $table->dropIndex('bills_customer_id_billing_month_index');

            // The bill list filters on status and on the month with no customer.
            $table->index(['status', 'billing_month']);
        });

        Schema::table('payments', function (Blueprint $table) {
            // Collection reports and the dashboard tiles sum by date range and
            // status; the existing index leads with customer_id and cannot help.
            $table->index(['payment_date', 'status']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            // Profit & Loss groups by category inside a date range.
            $table->index(['expense_date', 'expense_category_id']);
            // Superseded: a strict prefix of the index above.
            $table->dropIndex('expenses_expense_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['serial_no']);
            $table->dropIndex(['status', 'sheet_id']);
            $table->dropIndex(['connection_date']);
        });

        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropIndex(['status', 'reading_date']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->index(['customer_id', 'billing_month']);
            $table->dropIndex('bills_customer_month_id_index');
            $table->dropIndex(['status', 'billing_month']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_date', 'status']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('expense_date');
            $table->dropIndex(['expense_date', 'expense_category_id']);
        });
    }
};
