<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('timezone')->default('UTC')->after('settings');
            $table->string('currency', 3)->default('USD')->after('timezone');
            $table->string('billing_email')->nullable()->after('currency');
            $table->text('billing_address')->nullable()->after('billing_email');
            $table->unsignedTinyInteger('refund_policy_days')->nullable()->after('billing_address');
            $table->decimal('refund_policy_percentage', 5, 2)->nullable()->after('refund_policy_days');
            $table->string('stripe_customer_id')->nullable()->after('refund_policy_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'currency',
                'billing_email',
                'billing_address',
                'refund_policy_days',
                'refund_policy_percentage',
                'stripe_customer_id',
            ]);
        });
    }
};
