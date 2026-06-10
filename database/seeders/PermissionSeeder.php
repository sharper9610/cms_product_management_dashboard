<?php

namespace Database\Seeders;


use App\Models\Payer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {


    $role1 = Role::create(['name' => 'Super-Admin', 'guard_name' => 'web']);
    $role2 = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
    $role3 = Role::create(['name' => 'User', 'guard_name' => 'web']);



    $permissions = [
      [
        'group_name' => 'user',
        'permissions' => [
          'user.view',
          'user.create',
          'user.edit',
          'user.delete',
        ]
      ],
      [
        'group_name' => 'role',
        'permissions' => [
          'role.view',
          'role.create',
          'role.edit',
          'role.delete',
        ]
      ],
      [
        'group_name' => 'home',
        'permissions' => [
          'home.view',
        ]
      ],
      [
        'group_name' => 'product',
        'permissions' => [
          'product.view',
          'product.edit',
        ]
      ],
       [
        'group_name' => 'activity',
        'permissions' => [
          'activity.view',
        ]
      ],

    ];

    for ($i = 0; $i < count($permissions); $i++) {
      $permissionGroup = $permissions[$i]['group_name'];
      for ($j = 0; $j < count($permissions[$i]['permissions']); $j++) {
        // Create Permission
        $permission = Permission::create(['name' => $permissions[$i]['permissions'][$j], 'group_name' => $permissionGroup, 'guard_name' => 'web']);

        if ($permission->name=='home.view'){
          $role2->givePermissionTo($permission);
          $role3->givePermissionTo($permission);

        }


        if ($permission->name=='user.view'){
          $role2->givePermissionTo($permission);
        }

        $role1->givePermissionTo($permission);

      }
    }
    $superAdminUser = \App\Models\User::factory()->create([
      'name' => 'Example Super-Admin user',
      'email' => 'superadmin@example.com',
      'role_id' => $role1->id,
      'password' => Hash::make('password'),
    ]);
    $superAdminUser->assignRole($role1);



    $adminUser = \App\Models\User::factory()->create([
      'name' => 'Admin user',
      'email' => 'admin@example.com',
      'role_id' => $role2->id,
      'password' => Hash::make('password'),
    ]);
    $adminUser->assignRole($role2);

  }
}
