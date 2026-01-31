<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class EmployeeController extends Controller
{
    /**
     * Get list of all employees with their permissions.
     */
    public function index()
    {
        // Fetch users who have the 'employee' role and load their direct permissions
        $employees = User::role('employee')->with('permissions')->get();
        return response()->json($employees);
    }

    /**
     * Get all permissions grouped by their category.
     * This is used for the Frontend "Add Employee" form.
     */
    public function getGroupedPermissions()
    {
        $permissions = Permission::all();

        // Logic to group permissions by the prefix (e.g., 'employee' from 'employee.create')
        $groupedPermissions = $permissions->groupBy(function ($item, $key) {
            // Split "employee.create" by "." and take the first part "employee"
            return explode('.', $item->name)[0];
        });

        return response()->json($groupedPermissions);
    }

    /**
     * Create a new employee and assign permissions.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'permissions' => 'array', // Array of permission names: ['employee.create', 'order.view']
            'permissions.*' => 'exists:permissions,name' // Ensure permission exists in DB
        ]);

        // Create the user
        $employee = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign the base role
        $employee->assignRole('employee');

        // Sync specific permissions (Direct permissions)
        if ($request->has('permissions')) {
            $employee->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => 'Employee created successfully',
            'employee' => $employee->load('permissions')
        ], 201);
    }

    /**
     * Update an existing employee.
     */
    public function update(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        // Validation (Email unique check ignores current user ID)
        $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $id,
            'permissions' => 'array',
        ]);

        // Update Basic Info
        if ($request->has('name')) $employee->name = $request->name;
        if ($request->has('email')) $employee->email = $request->email;
        if ($request->has('password')) $employee->password = Hash::make($request->password);

        $employee->save();

        // Update Permissions
        if ($request->has('permissions')) {
            $employee->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => 'Employee updated successfully',
            'employee' => $employee->load('permissions')
        ]);
    }

    /**
     * Delete an employee.
     */
    public function destroy($id)
    {
        $employee = User::findOrFail($id);

        // Prevent deleting Super Admin or Self (optional safety)
        if ($employee->hasRole('super-admin')) {
            return response()->json(['message' => 'Cannot delete Super Admin'], 403);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully']);
    }
}
