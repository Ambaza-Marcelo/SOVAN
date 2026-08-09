<?php

namespace App\Http\Controllers\Backend\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Exports\AttendanceExport;
use App\Exports\FirstInLastOutExport;
use Excel;
use PDF;

class AbsenceController extends Controller
{
    //
    public $user;
    private $biotimeUrl;
    private $biotimeUsername;
    private $biotimePassword;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });

        // Configuration Biotime
        $this->biotimeUrl = env('GUZZLE_BIOTIME_URL', 'http://192.168.1.2:8080');
        $this->biotimeUsername = env('BIOTIME_USERNAME', 'Marcellin');
        $this->biotimePassword = env('BIOTIME_PASSWORD', 'marcelo@2001');
    }

    /**
     * Obtenir le token d'authentification Biotime
     */
    private function getAuthToken()
    {
        try {
            $client = new Client([
                'base_uri' => $this->biotimeUrl,
                'timeout' => 30.0,
                'verify' => false
            ]);

            // Essayer les endpoints d'authentification selon la documentation Biotime
            // 1. JWT Auth Token (recommandé)
            $endpoints = [
                '/jwt-api-token-auth/',
                '/api-token-auth/'
            ];

            foreach ($endpoints as $endpoint) {
                try {
                    $response = $client->post($endpoint, [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => [
                            'username' => $this->biotimeUsername,
                            'password' => $this->biotimePassword
                        ]
                    ]);

                    $statusCode = $response->getStatusCode();
                    $body = $response->getBody()->getContents();
                    $data = json_decode($body, true);
                    
                    Log::info('Biotime Auth Response', [
                        'endpoint' => $endpoint,
                        'status' => $statusCode,
                        'response' => $data
                    ]);
                    
                if (isset($data['token'])) {
                    return $data['token'];
                }
                    
                    if (isset($data['access_token'])) {
                        return $data['access_token'];
                    }
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    $response = $e->getResponse();
                    $body = $response ? $response->getBody()->getContents() : 'No response body';
                    Log::warning('Biotime Auth Error for ' . $endpoint . ': ' . $body);
                    continue;
                } catch (\Exception $e) {
                    Log::warning('Biotime Auth Exception for ' . $endpoint . ': ' . $e->getMessage());
                    continue;
                }
            }
            
            Log::error('Biotime Auth: All endpoints failed');
            return null;

        } catch (\Exception $e) {
            Log::error('Biotime Auth Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les données de présence depuis l'API Biotime
     */
    private function getAttendanceData($startDate = null, $endDate = null, $employeeIds = [], $departmentIds = [])
    {
        $token = $this->getAuthToken();
        
        if (!$token) {
            return ['error' => 'Impossible de se connecter à l\'API Biotime. Vérifiez les identifiants et l\'URL dans le fichier .env'];
        }

        try {
            $client = new Client([
                'base_uri' => $this->biotimeUrl,
                'timeout' => 30.0,
                'verify' => false
            ]);

            // Préparer les paramètres de la requête selon la documentation Biotime
            // Endpoint: /att/api/transactionReport/
            $params = [
                'page' => 1,
                'page_size' => 1000
            ];
            
            if ($startDate) {
                $params['start_date'] = $startDate;
            }
            
            if ($endDate) {
                $params['end_date'] = $endDate;
            }
            
            if (!empty($departmentIds)) {
                $params['departments'] = is_array($departmentIds) ? implode(',', $departmentIds) : $departmentIds;
            }

            // Utiliser l'endpoint correct selon la documentation
            $endpoint = '/att/api/transactionReport/';
            
            try {
                $response = $client->get($endpoint, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'JWT ' . $token
                    ],
                    'query' => $params
                ]);
            
                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);
                
                Log::info('Biotime Attendance Response', [
                    'status' => $statusCode,
                    'params' => $params,
                    'data_keys' => is_array($data) ? array_keys($data) : 'not array',
                    'sample_record' => isset($data['data'][0]) ? $data['data'][0] : (isset($data['results'][0]) ? $data['results'][0] : null)
                ]);
                
                // Extraire les données brutes
                $rawData = [];
                if (isset($data['data']) && is_array($data['data'])) {
                    $rawData = $data['data'];
                } elseif (isset($data['results']) && is_array($data['results'])) {
                    $rawData = $data['results'];
                } elseif (is_array($data) && isset($data[0])) {
                    $rawData = $data;
                } elseif (isset($data['attendance']) && is_array($data['attendance'])) {
                    $rawData = $data['attendance'];
                }
                
                // Traiter et normaliser les données
                $processedData = [];
                foreach ($rawData as $record) {
                    $processed = $this->processAttendanceRecord($record);
                    if ($processed) {
                        $processedData[] = $processed;
                    }
                }
                
                return ['data' => $processedData, 'total' => count($processedData)];
                
                // Si la réponse contient des erreurs
                if (isset($data['error']) || isset($data['detail'])) {
                    return ['error' => $data['error'] ?? $data['detail'] ?? 'Erreur inconnue de l\'API'];
                }
                
                return ['error' => 'Format de réponse non reconnu: ' . json_encode(array_keys($data ?? []))];
                
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $response = $e->getResponse();
                $body = $response ? $response->getBody()->getContents() : 'No response body';
                $statusCode = $response ? $response->getStatusCode() : 0;
                
                Log::error('Biotime API ClientException', [
                    'status' => $statusCode,
                    'body' => $body,
                    'message' => $e->getMessage()
                ]);
                
                return ['error' => 'Erreur API (Status: ' . $statusCode . '): ' . $body];
            }

        } catch (\Exception $e) {
            Log::error('Biotime API Error: ' . $e->getMessage());
            return ['error' => 'Erreur lors de la récupération des données: ' . $e->getMessage()];
        }
    }

    public function index(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_absence.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de voir les présences! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $attendanceData = [];
        $error = null;
        
        // Récupérer les paramètres de filtrage
        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $employeeIds = $request->get('employee_ids', []);
        $departmentIds = $request->get('department_ids', []);

        // Récupérer les départements et employés pour les filtres
        $departments = $this->getDepartments();
        $employees = $this->getEmployees();

        // Récupérer les données de présence
        $result = $this->getAttendanceData($startDate, $endDate, $employeeIds, $departmentIds);
                
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            // Les données sont déjà traitées et normalisées par getAttendanceData
            if (isset($result['data']) && is_array($result['data'])) {
                $attendanceData = $result['data'];
            } else {
                $attendanceData = [];
            }
        }

        return view('backend.pages.hr.absence.index', compact('attendanceData', 'error', 'startDate', 'endDate', 'departments', 'employees'));
    }

    /**
     * Rapport First In Last Out : premier entrée, dernier entrée et total heures par jour.
     * Utilise l'API Biotime (transactionReport) pour afficher combien d'heures l'employé travaille par jour.
     * Une seule ligne par employé et par jour (agrégation First In / Last Out).
     */
    public function firstInLastOut(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_absence.view')) {
            abort(403, 'Pardon!! vous n\'avez pas l\'autorisation de voir les présences! Mufise ico mubaza hamagara kuri 130 canke 122');
        }

        $attendanceData = [];
        $error = null;

        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $employeeIds = $request->get('employee_ids', []);
        $departmentIds = $request->get('department_ids', []);

        $departments = $this->getDepartments();
        $employees = $this->getEmployees();

        $result = $this->getAttendanceData($startDate, $endDate, $employeeIds, $departmentIds);

        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            $rawData = $result['data'] ?? [];

            // Agréger par employé + date : une seule ligne par jour
            $grouped = [];

            $toSeconds = function ($timeStr) {
                if (empty($timeStr) || $timeStr === 'N/A') {
                    return null;
                }
                if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', $timeStr, $matches)) {
                    return (int) $matches[1] * 3600 + (int) $matches[2] * 60 + (isset($matches[3]) ? (int) $matches[3] : 0);
                }
                $ts = strtotime($timeStr);
                return $ts !== false ? $ts % 86400 : null;
            };

            foreach ($rawData as $record) {
                if (!is_array($record)) {
                    continue;
                }

                $empCode = $record['emp_code'] ?? null;
                $date = $record['date'] ?? null;

                if (!$empCode || !$date) {
                    $attendanceData[] = $record;
                    continue;
                }

                $key = $empCode . '|' . $date;

                $checkIn = $record['check_in'] ?? null;
                $checkOut = $record['check_out'] ?? null;

                $checkInSeconds = $toSeconds($checkIn);
                $checkOutSeconds = $toSeconds($checkOut);

                if (!isset($grouped[$key])) {
                    $grouped[$key] = $record;
                } else {
                    $existingInSeconds = $toSeconds($grouped[$key]['check_in'] ?? null);
                    $existingOutSeconds = $toSeconds($grouped[$key]['check_out'] ?? null);

                    if ($checkInSeconds !== null && ($existingInSeconds === null || $checkInSeconds < $existingInSeconds)) {
                        $grouped[$key]['check_in'] = $checkIn;
                    }

                    if ($checkOutSeconds !== null && ($existingOutSeconds === null || $checkOutSeconds > $existingOutSeconds)) {
                        $grouped[$key]['check_out'] = $checkOut;
                    }
                }
            }

            foreach ($grouped as &$row) {
                $row['total_time'] = $this->calculateDurationBiotime($row['check_in'] ?? 'N/A', $row['check_out'] ?? 'N/A');
            }
            unset($row);

            $attendanceData = array_values($grouped);
            usort($attendanceData, function ($a, $b) {
                $dateCmp = strcmp($a['date'] ?? '', $b['date'] ?? '');
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }
                $firstA = $a['first_name'] ?? '';
                $firstB = $b['first_name'] ?? '';
                $firstCmp = strcmp($firstA, $firstB);
                if ($firstCmp !== 0) {
                    return $firstCmp;
                }
                return strcmp($a['last_name'] ?? '', $b['last_name'] ?? '');
            });
        }

        return view('backend.pages.hr.absence.first_in_last_out', compact(
            'attendanceData', 'error', 'startDate', 'endDate', 'departments', 'employees'
        ));
    }

    /**
     * Export Excel du rapport de présence (liste brute).
     */
    public function exportIndexExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_absence.view')) {
            abort(403, 'Non autorisé');
        }

        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $employeeIds = $request->get('employee_ids', []);
        $departmentIds = $request->get('department_ids', []);

        $result = $this->getAttendanceData($startDate, $endDate, $employeeIds, $departmentIds);

        if (isset($result['error'])) {
            return redirect()->back()->with('error', $result['error']);
        }

        $attendanceData = $result['data'] ?? [];

        $fileName = 'rapport_presences_' . $startDate . '_au_' . $endDate . '.xlsx';

        return Excel::download(new AttendanceExport($attendanceData), $fileName);
    }

    /**
     * Export Excel du rapport First In Last Out.
     */
    public function exportFirstInLastOutExcel(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_absence.view')) {
            abort(403, 'Non autorisé');
        }

        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $employeeIds = $request->get('employee_ids', []);
        $departmentIds = $request->get('department_ids', []);

        $result = $this->getAttendanceData($startDate, $endDate, $employeeIds, $departmentIds);

        if (isset($result['error'])) {
            return redirect()->back()->with('error', $result['error']);
        }

        $rawData = $result['data'] ?? [];
        $grouped = [];

        $toSeconds = function ($timeStr) {
            if (empty($timeStr) || $timeStr === 'N/A') {
                return null;
            }
            if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', $timeStr, $matches)) {
                return (int) $matches[1] * 3600 + (int) $matches[2] * 60 + (isset($matches[3]) ? (int) $matches[3] : 0);
            }
            $ts = strtotime($timeStr);
            return $ts !== false ? $ts % 86400 : null;
        };

        foreach ($rawData as $record) {
            if (!is_array($record)) {
                continue;
            }

            $empCode = $record['emp_code'] ?? null;
            $date = $record['date'] ?? null;

            if (!$empCode || !$date) {
                continue;
            }

            $key = $empCode . '|' . $date;

            $checkIn = $record['check_in'] ?? null;
            $checkOut = $record['check_out'] ?? null;

            $checkInSeconds = $toSeconds($checkIn);
            $checkOutSeconds = $toSeconds($checkOut);

            if (!isset($grouped[$key])) {
                $grouped[$key] = $record;
            } else {
                $existingInSeconds = $toSeconds($grouped[$key]['check_in'] ?? null);
                $existingOutSeconds = $toSeconds($grouped[$key]['check_out'] ?? null);

                if ($checkInSeconds !== null && ($existingInSeconds === null || $checkInSeconds < $existingInSeconds)) {
                    $grouped[$key]['check_in'] = $checkIn;
                }

                if ($checkOutSeconds !== null && ($existingOutSeconds === null || $checkOutSeconds > $existingOutSeconds)) {
                    $grouped[$key]['check_out'] = $checkOut;
                }
            }
        }

        foreach ($grouped as &$row) {
            $row['total_time'] = $this->calculateDurationBiotime($row['check_in'] ?? 'N/A', $row['check_out'] ?? 'N/A');
        }
        unset($row);

        $attendanceData = array_values($grouped);

        $fileName = 'rapport_first_in_last_out_' . $startDate . '_au_' . $endDate . '.xlsx';

        return Excel::download(new FirstInLastOutExport($attendanceData), $fileName);
    }

    /**
     * API endpoint pour récupérer les données via AJAX
     */
    public function getAttendance(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_absence.view')) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $employeeIds = $request->get('employee_ids', []);
        $departmentIds = $request->get('department_ids', []);

        $result = $this->getAttendanceData($startDate, $endDate, $employeeIds, $departmentIds);

        return response()->json($result);
    }

    /**
     * Récupérer la liste des départements depuis l'API Biotime
     */
    private function getDepartments()
    {
            $token = $this->getAuthToken();
            
        if (!$token) {
            return [];
        }

        try {
            $client = new Client([
                'base_uri' => $this->biotimeUrl,
                'timeout' => 30.0,
                'verify' => false
            ]);

            $response = $client->get('/personnel/api/departments/', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'JWT ' . $token
                ],
                'query' => [
                    'page_size' => 1000,
                    'ordering' => 'dept_name'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['data']) && is_array($data['data'])) {
                return $data['data'];
            }
            
            return [];

        } catch (\Exception $e) {
            Log::error('Biotime Departments Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer la liste des employés depuis l'API Biotime
     */
    private function getEmployees($departmentId = null)
    {
        $token = $this->getAuthToken();
        
        if (!$token) {
            return [];
        }

        try {
            $client = new Client([
                'base_uri' => $this->biotimeUrl,
                'timeout' => 30.0,
                'verify' => false
            ]);

                $params = [
                'page_size' => 1000,
                'ordering' => 'first_name,last_name'
            ];
            
            if ($departmentId) {
                $params['department'] = $departmentId;
                }
                
            $response = $client->get('/personnel/api/employees/', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'JWT ' . $token
                ],
                'query' => $params
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['data']) && is_array($data['data'])) {
                return $data['data'];
                }
            
            return [];

        } catch (\Exception $e) {
            Log::error('Biotime Employees Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * API endpoint pour récupérer les départements
     */
    public function getDepartmentsApi()
    {
        if (is_null($this->user) || !$this->user->can('hr_absence.view')) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $departments = $this->getDepartments();
        return response()->json(['data' => $departments]);
    }

    /**
     * API endpoint pour récupérer les employés
     */
    public function getEmployeesApi(Request $request)
    {
        if (is_null($this->user) || !$this->user->can('hr_absence.view')) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $departmentId = $request->get('department_id');
        $employees = $this->getEmployees($departmentId);
        return response()->json(['data' => $employees]);
    }

    /**
     * Traiter et normaliser un enregistrement de présence
     */
    private function processAttendanceRecord($record)
    {
        if (!is_array($record)) {
            return null;
        }

        $processed = [];
        
        // Employee information - peut être dans un objet employee
        if (isset($record['employee']) && is_array($record['employee'])) {
            $emp = $record['employee'];
            $processed['emp_code'] = $emp['emp_code'] ?? $emp['employee_no'] ?? $emp['emp_id'] ?? 'N/A';
            $processed['first_name'] = $emp['first_name'] ?? $emp['firstname'] ?? '';
            $processed['last_name'] = $emp['last_name'] ?? $emp['lastname'] ?? '';
        } else {
            $processed['emp_code'] = $record['emp_code'] ?? $record['employee_no'] ?? $record['emp_id'] ?? $record['employee_code'] ?? 'N/A';
            $processed['first_name'] = $record['first_name'] ?? $record['firstname'] ?? '';
            $processed['last_name'] = $record['last_name'] ?? $record['lastname'] ?? '';
                }
                
        // Department information - peut être un objet ou une chaîne
        if (isset($record['department'])) {
            if (is_array($record['department'])) {
                $processed['dept_name'] = $record['department']['dept_name'] ?? $record['department']['name'] ?? 'N/A';
                $processed['dept_code'] = $record['department']['dept_code'] ?? $record['department']['code'] ?? '';
            } else {
                $processed['dept_name'] = $record['department'];
            }
        } elseif (isset($record['employee']['department']) && is_array($record['employee']['department'])) {
            $dept = $record['employee']['department'];
            $processed['dept_name'] = $dept['dept_name'] ?? $dept['name'] ?? 'N/A';
        } else {
            $processed['dept_name'] = $record['dept_name'] ?? $record['department_name'] ?? 'N/A';
        }
        
        // Position information - peut être un objet ou une chaîne
        if (isset($record['position'])) {
            if (is_array($record['position'])) {
                $processed['position'] = $record['position']['pos_name'] ?? $record['position']['name'] ?? $record['position']['position_name'] ?? 'N/A';
            } else {
                $processed['position'] = $record['position'];
            }
        } elseif (isset($record['employee']['position']) && is_array($record['employee']['position'])) {
            $pos = $record['employee']['position'];
            $processed['position'] = $pos['pos_name'] ?? $pos['name'] ?? $pos['position_name'] ?? 'N/A';
        } else {
            $processed['position'] = $record['pos_name'] ?? $record['position_name'] ?? $record['job_title'] ?? 'N/A';
        }
        
        // Date information - essayer plusieurs formats
        $dateValue = $record['date'] ?? $record['punch_date'] ?? $record['att_date'] ?? $record['attendance_date'] ?? null;
        
        // Si la date est dans un objet datetime
        if (isset($record['punch_time'])) {
            $punchTime = $record['punch_time'];
            if (is_string($punchTime) && strpos($punchTime, ' ') !== false) {
                $dateValue = explode(' ', $punchTime)[0];
            }
        }
        
        if ($dateValue) {
            try {
                // Gérer différents formats de date
                if (is_numeric($dateValue)) {
                    $dateObj = date('Y-m-d', $dateValue);
                } else {
                    // Essayer de parser la date
                    $timestamp = strtotime($dateValue);
                    if ($timestamp !== false) {
                        $dateObj = date('Y-m-d', $timestamp);
                    } else {
                        // Si c'est au format YYYY-MM-DD, utiliser directement
                        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateValue)) {
                            $dateObj = substr($dateValue, 0, 10);
                        } else {
                            $dateObj = $dateValue;
                        }
                    }
                }
                $processed['date'] = $dateObj;
                $processed['weekday'] = $this->getWeekdayName($dateObj);
            } catch (\Exception $e) {
                $processed['date'] = $dateValue;
                $processed['weekday'] = 'N/A';
            }
        } else {
            $processed['date'] = 'N/A';
            $processed['weekday'] = 'N/A';
        }
        
        // Check In (First punch) - essayer plusieurs champs possibles
        // Selon la doc Biotime (att_report), les champs peuvent s'appeler
        // checkin_time / checkout_time. On les mappe explicitement ici.
        $checkIn = $record['check_in']
            ?? $record['checkin_time']
            ?? $record['checkin']
            ?? $record['first_punch']
            ?? $record['first_in']
            ?? $record['first_punch_time']
            ?? $record['in_time']
            ?? $record['start_time']
            ?? null;
        
        // Si check_in est dans un objet datetime ou punch_time
        if (isset($record['check_in_datetime'])) {
            $checkIn = $record['check_in_datetime'];
        } elseif (isset($record['punch_time']) && !isset($record['check_out'])) {
            // Si c'est le premier punch de la journée
            $checkIn = $record['punch_time'];
        }
        
        // Format Biotime First In Last Out : HH:MM (ex. 08:03, 15:30)
        if ($checkIn) {
            $processed['check_in'] = $this->formatTimeBiotime($checkIn);
        } else {
            $processed['check_in'] = 'N/A';
        }

        // Check Out (Last punch) - essayer plusieurs champs possibles
        $checkOut = $record['check_out']
            ?? $record['checkout_time']
            ?? $record['checkout']
            ?? $record['last_punch']
            ?? $record['last_out']
            ?? $record['last_punch_time']
            ?? $record['out_time']
            ?? $record['end_time']
            ?? null;

        if (isset($record['check_out_datetime'])) {
            $checkOut = $record['check_out_datetime'];
        }

        if ($checkOut) {
            $processed['check_out'] = $this->formatTimeBiotime($checkOut);
        } else {
            $processed['check_out'] = 'N/A';
        }

        // Total Time - format Biotime : HH:MM (ex. 07:26 = 7h 26min)
        $totalTime = $record['total_time'] ?? $record['work_time'] ?? $record['duration'] ?? $record['work_duration'] ??
                     $record['total_work_time'] ?? $record['attendance_duration'] ?? $record['time_duration'] ??
                     $record['work_hours'] ?? null;

        if ($totalTime) {
            $processed['total_time'] = $this->formatDurationBiotime($totalTime);
        } elseif ($processed['check_in'] !== 'N/A' && $processed['check_out'] !== 'N/A') {
            $processed['total_time'] = $this->calculateDurationBiotime($processed['check_in'], $processed['check_out']);
        } else {
            $processed['total_time'] = 'N/A';
        }
        
        return $processed;
    }
    
    /**
     * Formater l'heure (format standard HH:MM:SS)
     */
    private function formatTime($time)
    {
        if (empty($time) || $time === 'N/A') {
            return 'N/A';
        }
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
            return $time;
        }
        if (is_numeric($time)) {
            return date('H:i:s', $time);
        }
        try {
            $timestamp = strtotime($time);
            if ($timestamp !== false) {
                return date('H:i:s', $timestamp);
            }
        } catch (\Exception $e) {
            // Ignorer
        }
        return $time;
    }

    /**
     * Formater l'heure au format Biotime First In Last Out : HH:MM (ex. 08:03, 15:30)
     */
    private function formatTimeBiotime($time)
    {
        if (empty($time) || $time === 'N/A') {
            return 'N/A';
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $time, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }
        if (is_numeric($time)) {
            return date('H:i', $time);
        }
        try {
            $timestamp = strtotime($time);
            if ($timestamp !== false) {
                return date('H:i', $timestamp);
            }
        } catch (\Exception $e) {
            // Ignorer
        }
        return $time;
    }

    /**
     * Formater la durée au format Biotime : HH:MM (ex. 07:26 = 7h 26min, sans secondes)
     */
    private function formatDurationBiotime($duration)
    {
        if (empty($duration) || $duration === 'N/A') {
            return 'N/A';
        }
        if (is_string($duration) && preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $duration, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }
        if (is_numeric($duration)) {
            $hours = (int) floor($duration / 3600);
            $minutes = (int) floor(($duration % 3600) / 60);
            return sprintf('%02d:%02d', $hours, $minutes);
        }
        return $duration;
    }

    /**
     * Calculer la durée entre deux heures, au format Biotime HH:MM
     */
    private function calculateDurationBiotime($checkIn, $checkOut)
    {
        if ($checkIn === 'N/A' || $checkOut === 'N/A' || empty($checkIn) || empty($checkOut)) {
            return 'N/A';
        }
        $parseTime = function ($timeStr) {
            if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', $timeStr, $matches)) {
                return (int) $matches[1] * 3600 + (int) $matches[2] * 60 + (isset($matches[3]) ? (int) $matches[3] : 0);
            }
            $ts = strtotime($timeStr);
            return $ts !== false ? $ts % 86400 : false;
        };
        $t1 = $parseTime($checkIn);
        $t2 = $parseTime($checkOut);
        if ($t1 === false || $t2 === false) {
            return 'N/A';
        }
        if ($t2 < $t1) {
            $t2 += 86400;
        }
        return $this->formatDurationBiotime($t2 - $t1);
    }

    /**
     * Formater la durée
     */
    private function formatDuration($duration)
    {
        if (empty($duration) || $duration === 'N/A') {
            return 'N/A';
        }
        
        // Si c'est déjà formaté (ex: "08:30:00" ou "8h 30m")
        if (is_string($duration) && (strpos($duration, ':') !== false || strpos($duration, 'h') !== false)) {
            return $duration;
        }
        
        // Si c'est en secondes
        if (is_numeric($duration)) {
            $hours = floor($duration / 3600);
            $minutes = floor(($duration % 3600) / 60);
            $seconds = $duration % 60;
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return $duration;
    }
    
    /**
     * Calculer la durée entre deux heures
     */
    private function calculateDuration($checkIn, $checkOut)
    {
        if ($checkIn === 'N/A' || $checkOut === 'N/A' || empty($checkIn) || empty($checkOut)) {
            return 'N/A';
        }
        
        try {
            // Extraire les heures et minutes si format HH:MM ou HH:MM:SS
            $parseTime = function($timeStr) {
                if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', $timeStr, $matches)) {
                    $hours = (int)$matches[1];
                    $minutes = (int)$matches[2];
                    $seconds = isset($matches[3]) ? (int)$matches[3] : 0;
                    return $hours * 3600 + $minutes * 60 + $seconds;
                }
                // Essayer avec strtotime
                $timestamp = strtotime($timeStr);
                if ($timestamp !== false) {
                    return $timestamp % 86400; // Seulement la partie heure de la journée
                }
                return false;
            };
            
            $time1 = $parseTime($checkIn);
            $time2 = $parseTime($checkOut);
            
            if ($time1 === false || $time2 === false) {
                return 'N/A';
            }
            
            // Si check_out est avant check_in, on suppose que c'est le lendemain
            if ($time2 < $time1) {
                $time2 += 86400; // Ajouter 24 heures
            }
            
            $diff = $time2 - $time1;
            return $this->formatDuration($diff);
        } catch (\Exception $e) {
            Log::warning('Error calculating duration: ' . $e->getMessage());
            return 'N/A';
        }
    }
    
    /**
     * Obtenir le nom du jour de la semaine en français
     */
    private function getWeekdayName($date)
    {
        $days = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche'
        ];
        
        try {
            $dayName = date('l', strtotime($date));
            return $days[$dayName] ?? $dayName;
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}
