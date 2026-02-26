<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class EmployeeController extends Controller
{
    protected $apiBaseUrl;
    
    public function __construct()
    {
        $this->apiBaseUrl = env('ECOSYSTEM_API_URL', 'http://localhost:8000/api');
    }
    
    public function index(Request $request)
    {
        try {
            $token = Session::get('auth_token');
            
            $response = Http::withToken($token)
                ->get($this->apiBaseUrl . '/employees');
            
            if ($response->successful()) {
                $employees = $response->json()['data'] ?? [];
                return view('employees.index', compact('employees'));
            }
            
            return view('employees.index', ['employees' => []]);
            
        } catch (\Exception $e) {
            \Log::error('Employees Index Error: ' . $e->getMessage());
            return view('employees.index', ['employees' => []]);
        }
    }
}