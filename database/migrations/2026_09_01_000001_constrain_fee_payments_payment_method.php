<?php

use App\Enums\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CHECK = 'fee_payments_payment_method_check';

    /**
     * `payment_method` was a plain string, enforced only by the `in:` rule in
     * Admin\FeesController. Anything writing outside that path — seeder, artisan
     * command, queued job — could store any value at all. Push the constraint
     * down to the database so the enum cast is not the only thing holding it.
     *
     * `enum()` compiles to a native ENUM on MySQL/MariaDB and to
     * `varchar check (... in (...))` on SQLite, so the tests get the same
     * enforcement the dev database does.
     *
     * On MySQL/MariaDB the ENUM is backed by an explicit CHECK as well, because
     * an ENUM only *errors* under a strict `sql_mode` — Laravel sets that from
     * `'strict' => true` in config/database.php, which is app configuration, and
     * a non-strict session silently coerces an unknown value to `''` instead.
     * The CHECK holds regardless of `sql_mode`. (Supported by MariaDB 10.2.1+
     * and MySQL 8.0.16+; older MySQL parses and ignores it, leaving the ENUM as
     * the only guard, which is still no worse than the bare string was.)
     */
    public function up(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->enum('payment_method', PaymentMethod::values())
                ->default(PaymentMethod::Cash->value)
                ->change();
        });

        if ($this->usesMysql()) {
            DB::statement(sprintf(
                'ALTER TABLE fee_payments ADD CONSTRAINT %s CHECK (payment_method IN (%s))',
                self::CHECK,
                $this->allowedList()
            ));
        }
    }

    public function down(): void
    {
        if ($this->usesMysql()) {
            DB::statement('ALTER TABLE fee_payments DROP CONSTRAINT '.self::CHECK);
        }

        Schema::table('fee_payments', function (Blueprint $table) {
            $table->string('payment_method')
                ->default(PaymentMethod::Cash->value)
                ->change();
        });
    }

    private function usesMysql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    /**
     * Quoted allow-list for the CHECK body. The values come from the enum, not
     * from input, so there is nothing to bind here.
     */
    private function allowedList(): string
    {
        return collect(PaymentMethod::values())
            ->map(fn (string $value) => "'".$value."'")
            ->implode(', ');
    }
};
