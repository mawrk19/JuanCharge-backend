<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UnifyUsersTableWithRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Create Roles table
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });

            // Seed basic roles
            DB::table('roles')->insert([
                ['name' => 'Super Admin', 'slug' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'LGU Admin', 'slug' => 'lgu_admin', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'LGU Staff', 'slug' => 'lgu_staff', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Kiosk User', 'slug' => 'kiosk_user', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // 2. Modify users table
        Schema::table('users', function (Blueprint $table) {
            // Existing fields from other tables
            if (!Schema::hasColumn('users', 'first_name')) $table->string('first_name')->nullable()->after('name');
            if (!Schema::hasColumn('users', 'last_name')) $table->string('last_name')->nullable()->after('first_name');
            if (!Schema::hasColumn('users', 'phone_number')) $table->string('phone_number', 15)->nullable()->after('email');
            
            // Multi-tenancy and RBAC
            if (!Schema::hasColumn('users', 'role_id')) $table->unsignedBigInteger('role_id')->nullable()->after('id');
            if (!Schema::hasColumn('users', 'lgu_id')) $table->unsignedBigInteger('lgu_id')->nullable()->after('role_id');
            
            // Kiosk specific fields (nullable for admins)
            if (!Schema::hasColumn('users', 'points_balance')) $table->integer('points_balance')->default(0)->after('password');
            if (!Schema::hasColumn('users', 'points_total')) $table->integer('points_total')->default(0)->after('points_balance');
            if (!Schema::hasColumn('users', 'points_used')) $table->integer('points_used')->default(0)->after('points_total');
            
            // Auth/Status fields
            if (!Schema::hasColumn('users', 'is_first_login')) $table->boolean('is_first_login')->default(true)->after('points_used');
            if (!Schema::hasColumn('users', 'status')) $table->string('status')->default('active')->after('is_first_login');
            if (!Schema::hasColumn('users', 'device_token')) $table->string('device_token')->nullable()->after('status');
            if (!Schema::hasColumn('users', 'token_expires_at')) $table->timestamp('token_expires_at')->nullable()->after('device_token');
            if (!Schema::hasColumn('users', 'contact_number_verified_at')) $table->timestamp('contact_number_verified_at')->nullable()->after('email_verified_at');
            if (!Schema::hasColumn('users', 'total_recyclables_weight')) $table->decimal('total_recyclables_weight', 10, 2)->default(0)->after('points_used');
            if (!Schema::hasColumn('users', 'deleted_at')) $table->softDeletes();
            if (!Schema::hasColumn('users', 'total_recyclables_weight')) $table->decimal('total_recyclables_weight', 10, 2)->default(0)->after('points_used');

            // Foreign keys
            // We'll add these separately to avoid errors if they already exist or if the DB doesn't support them during the schema change
        });

        Schema::table('users', function (Blueprint $table) {
            try {
                $table->foreign('role_id')->references('id')->on('roles');
            } catch (\Exception $e) {}
            try {
                $table->foreign('lgu_id')->references('id')->on('lgus')->onDelete('set null');
            } catch (\Exception $e) {}
        });

        // 3. Mark existing admins as super_admin if not set
        DB::table('users')->whereNull('role_id')->update(['role_id' => 1]);

        // 4. Migrate LGU Users
        if (Schema::hasTable('lgu_users')) {
            $lguUsers = DB::table('lgu_users')->get();
            foreach ($lguUsers as $lu) {
                // Determine role: default to LGU Staff, or Admin if name suggests it
                $roleId = 3; // LGU Staff
                if (stripos($lu->email, 'admin') !== false) $roleId = 2; // LGU Admin

                DB::table('users')->updateOrInsert(
                    ['email' => $lu->email],
                    [
                        'name' => $lu->name ?? ($lu->first_name . ' ' . $lu->last_name),
                        'first_name' => $lu->first_name ?? null,
                        'last_name' => $lu->last_name ?? null,
                        'password' => $lu->password,
                        'role_id' => $roleId,
                        'lgu_id' => $lu->lgu_id ?? null,
                        'status' => $lu->status ?? 'active',
                        'is_first_login' => $lu->is_first_login ?? false,
                        'email_verified_at' => $lu->email_verified_at ?? null,
                        'created_at' => $lu->created_at,
                        'updated_at' => $lu->updated_at,
                    ]
                );
            }
        }

        // 5. Migrate Kiosk Users
        if (Schema::hasTable('kiosk_users')) {
            $kioskUsers = DB::table('kiosk_users')->get();
            foreach ($kioskUsers as $ku) {
                DB::table('users')->updateOrInsert(
                    ['email' => $ku->email],
                    [
                        'name' => $ku->name ?? ($ku->first_name . ' ' . $ku->last_name),
                        'first_name' => $ku->first_name ?? null,
                        'last_name' => $ku->last_name ?? null,
                        'password' => $ku->password,
                        'role_id' => 4, // Kiosk User
                        'phone_number' => $ku->contact_number ?? null,
                        'points_balance' => $ku->points_balance ?? 0,
                        'points_total' => $ku->points_total ?? 0,
                        'points_used' => $ku->points_used ?? 0,
                        'total_recyclables_weight' => $ku->total_recyclables_weight ?? 0,
                        'device_token' => $ku->device_token ?? null,
                        'token_expires_at' => $ku->token_expires_at ?? null,
                        'status' => $ku->status ?? 'active',
                        'contact_number_verified_at' => $ku->contact_number_verified_at ?? null,
                        'created_at' => $ku->created_at,
                        'updated_at' => $ku->updated_at,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            try { $table->dropForeign(['role_id']); } catch (\Exception $e) {}
            try { $table->dropForeign(['lgu_id']); } catch (\Exception $e) {}
            
            $table->dropColumn([
                'role_id', 'lgu_id', 'points_used', 
                'is_first_login', 'status', 'device_token', 'token_expires_at', 
                'contact_number_verified_at'
            ]);
            // Keep points_balance and points_total if they were added by previous migrations
        });
        Schema::dropIfExists('roles');
    }
}
