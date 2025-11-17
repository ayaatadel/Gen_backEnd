<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Support\Facades\Hash;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    // Authorization for admin routes is handled via route middleware in `routes/api.php`.
    // Removed inline constructor middleware to avoid depending on controller base implementation.

    // public function users()
    // {
    //     $users = User::with('profile')->where('role', '!=', 'admin')->paginate(10);
    //     return response()->json($users);
    // }
    public function users()
    {
        $users = User::with('profile')->paginate(10);
        return response()->json($users);
    }
    // public function addUser(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:255|unique:users',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:8|confirmed',
    //         'role' => 'required|in:user,admin',
    //     ], [
    //         'email.unique' => 'This email is already exist.',
    //         'name.unique' => 'This name is already exist.',
    //         'role.in' => 'Invalid role.',
    //         'role.required' => 'Role is required.',
    //         'email.required' => 'Email is required.',
    //         'email.email' => 'Invalid email format.',
    //         'email.max' => 'Email must be less than 255 characters.',
    //         'password.required' => 'Password is required.',
    //         'password.min' => 'Password must be at least 8 characters.',
    //         'name.required' => 'Name is required.',
    //         'name.max' => 'Name must be less than 255 characters.',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     $user = User::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),
    //         'role' => $request->role,
    //     ]);

    //     return response()->json([
    //         'message' => 'User added successfully',
    //         'user' => $user,
    //     ], 201);
    // }


    public function addUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,admin',
        ], [
            'email.unique' => 'This email is already exist.',
            'name.unique' => 'This name is already exist.',
            'role.in' => 'Invalid role.',
            'role.required' => 'Role is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Invalid email format.',
            'email.max' => 'Email must be less than 255 characters.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'name.required' => 'Name is required.',
            'name.max' => 'Name must be less than 255 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'User added successfully',
            'user' => $user,
        ], 201);
    }

    // public function updateUser(Request $request, User $user)

    // {
    //     $validator = Validator::make($request->all(), [
    //         'title' => 'sometimes|string|max:255',
    //         'description' => 'sometimes|string',
    //         'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
    //         'password' => 'sometimes|string|min:8|confirmed',
    //         'role' => 'sometimes|in:user,admin',
    //         'skills' => 'sometimes|array',
    //         'skills.*.title' => 'required_with:skills|string|max:255',
    //         'skills.*.years_of_experience' => 'required_with:skills|integer|min:0',
    //         'skills.*.proficiency_level' => 'required_with:skills|in:beginner,intermediate,expert',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     try {
    //         $user->update($request->all());
    //         $user->save();
    //         return response()->json([
    //             'message' => 'user updated successfully',
    //             'user' => $user,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to update user',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function updateUser(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255||unique:users,name,',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'role' => 'sometimes|in:user,admin',
            'skills' => 'sometimes|array',
            'skills.*.title' => 'required_with:skills|string|max:255',
            'skills.*.years_of_experience' => 'required_with:skills|integer|min:0',
            'skills.*.proficiency_level' => 'required_with:skills|in:beginner,intermediate,expert',
        ]);

        if ($validator->fails()) {
            // Special message for duplicated email
            if (isset($validator->errors()->toArray()['email'])) {
                return response()->json([
                    'message' => 'This email is already exist.',
                ], 422);
            }

            return response()->json($validator->errors(), 422);
        }

        try {
            // Store old values before updating
            $oldData = $user->only(['name', 'email', 'role']);

            // Update user data
            $user->update($request->only(['name', 'email', 'role']));

            if ($request->filled('password')) {
                $user->password = bcrypt($request->password);
            }

            $user->save();

            // Compare old and new data to know what changed
            $changes = [];
            foreach ($oldData as $key => $oldValue) {
                if ($user->$key !== $oldValue) {
                    switch ($key) {
                        case 'name':
                            $changes[] = 'Name was updated successfully.';
                            break;
                        case 'email':
                            $changes[] = 'Email was updated successfully.';
                            break;
                        case 'role':
                            $changes[] = 'Role was updated successfully.';
                            break;
                    }
                }
            }

            if ($request->filled('password')) {
                $changes[] = 'Password was updated successfully.';
            }

            $message = !empty($changes)
                ? implode(' ', $changes)
                : 'No changes were made.';

            return response()->json([
                'message' => $message,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function jobs()
    {
        $jobs = Job::with(['company', 'applications'])->paginate(10);
        return response()->json($jobs);
    }
    public function companies()
    {
        $companies = Company::paginate(10);
        return response()->json($companies);
    }


    public function createJob(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'required|string',
            'location' => 'required|string|max:255',
            'type' => 'required|in:full-time,part-time,contract',
            'salary_from' => 'nullable|numeric|min:0',
            'salary_to' => 'nullable|numeric|gt:salary_from',
            'deadline' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $job = Job::create($request->all());

        return response()->json([
            'message' => 'Job created successfully',
            'job' => $job->load('company'),
        ], 201);
    }

    public function createCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $company = new Company($request->except('logo'));

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('company-logos', 'public');
            $company->logo = $path;
        }

        $company->save();

        return response()->json([
            'message' => 'Company created successfully',
            'company' => $company,
        ], 201);
    }

    public function updateJobStatus(Request $request, Job $job)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $job->update(['is_active' => $request->is_active]);
        // $job->update($request->all());

        return response()->json([
            'message' => 'Job status updated successfully',
            'job' => $job,
        ]);
    }


    public function updateJob(Request $request, Job $job)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'salary' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'deadline' => 'sometimes|date|after:today',
            'requirements' => 'sometimes|string',
            'location' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:full-time,part-time,contract',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $job->update($request->all());
            $job->save();
            return response()->json([
                'message' => 'Job updated successfully',
                'job' => $job,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function updateCompany(Request $request, Company $company)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'title' => 'sometimes|string|max:255',
    //         'description' => 'sometimes|string',
    //         'salary' => 'sometimes|numeric|min:0',
    //         'is_active' => 'sometimes|boolean',
    //         'deadline' => 'sometimes|date|after:today',
    //         'requirements' => 'sometimes|string',
    //         'location' => 'sometimes|string|max:255',
    //         'type' => 'sometimes|in:full-time,part-time,contract',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     try {
    //         $company->update($request->all());
    //         $company->save();
    //         return response()->json([
    //             'message' => '$company updated successfully',
    //             '$company' => $company,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to update coma$company',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // public function updateCompany(Request $request, Company $company)
    // {
    //     // Validation: allow logo to be a string (URL) or a file upload
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'sometimes|string|max:255',
    //         'location' => 'sometimes|string|max:255',
    //         'description' => 'sometimes|string',
    //         'website' => 'sometimes|url',
    //         'logo' => 'sometimes|nullable|string|file|mimes:jpg,jpeg,png,svg|max:2048',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     try {
    //         // Handle file upload if a file is provided
    //         if ($request->hasFile('logo')) {
    //             $file = $request->file('logo');
    //             $filename = time() . '_' . $file->getClientOriginalName();
    //             $file->move(public_path('uploads/logos'), $filename);

    //             // Replace the 'logo' input with the uploaded filename
    //             $request->merge(['logo' => $filename]);
    //         }

    //         // Update company with validated fields
    //         $company->update($request->only([
    //             'name',
    //             'location',
    //             'description',
    //             'website',
    //             'logo',
    //         ]));

    //         return response()->json([
    //             'message' => 'Company updated successfully',
    //             'company' => $company,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to update company',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

public function updateCompany(Request $request, Company $company)
{
    // Validate everything except logo
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
        'location' => 'sometimes|string|max:255',
        'description' => 'sometimes|string',
        'website' => 'sometimes|url',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    try {
        // Handle 'logo' separately
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/logos'), $filename);
            $logo = $filename;
        } elseif ($request->has('logo')) {
            // If logo is sent as string
            $logo = $request->input('logo');
        } else {
            $logo = $company->logo; // keep existing logo if nothing is sent
        }

        // Update company with all fields
        $company->update([
            'name' => $request->input('name', $company->name),
            'location' => $request->input('location', $company->location),
            'description' => $request->input('description', $company->description),
            'website' => $request->input('website', $company->website),
            'logo' => $logo,
        ]);

        return response()->json([
            'message' => 'Company updated successfully',
            'company' => $company,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to update company',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function updateApplicationStatus(Request $request, $applicationId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,reviewed,shortlisted,rejected,accepted',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $application = JobApplication::findOrFail($applicationId);
        $application->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Application status updated successfully',
            'application' => $application->load(['user', 'job']),
        ]);
    }

    public function deleteUser($userID)
    {

        try {
            $user = user::findOrFail($userID);
            $user->delete();
            return response()->json([
                'message' => 'user deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function deleteJob($jobID)
    {

        try {
            $job = Job::findOrFail($jobID);
            $job->delete();
            return response()->json([
                'message' => 'Job deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteCompany($companyID)
    {

        try {
            $company = Job::findOrFail($companyID);
            $company->delete();
            return response()->json([
                'message' => 'company deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Company',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
