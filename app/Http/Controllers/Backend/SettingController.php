<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use \App\Models\Setting;

class SettingController extends Controller
{
    //
    public function index()
    {
        $settings = Setting::orderBy('created_at', 'desc')->get();
        return view('backend.pages.setting.index', compact('settings'));
    }
    public function create()
    {
    	return view('backend.pages.setting.create');
    }

     public function store(Request $request)
    {
        //
        $request->validate([
            'name' => 'required',
            'nif' => 'required',
            'rc' => 'required',
            'commune' => 'required',
            'province' => 'required',
            'zone' => 'required',

            'tp_type' => 'required',
            'vat_taxpayer' => 'required',
            'ct_taxpayer' => 'required',
            'tl_taxpayer' => 'required',
            'tp_fiscal_center' => 'required',
            'tp_activity_sector' => 'required',
            'tp_legal_form' => 'required',
            'postal_number' => 'required',
            'quartier' => 'required',
            'rue' => 'required',
            'max_line' => 'required',
            'logo' => 'required|mimes:jpeg,jpg,png,svg|max:2048',

        ]);

        

        $storagepath = $request->file('logo')->store('public/logo');
        $fileName = basename($storagepath);

        $data = $request->except(['bank_name', 'account_number']);
        $data['logo'] = $fileName;
        $data['bank_name'] = $this->sanitizeBankArrays($request->bank_name ?? []);
        $data['account_number'] = $this->sanitizeBankArrays($request->account_number ?? []);

        Setting::create($data);

        return redirect()->route('admin.settings.index')->with('success', __('messages.setting_created'));
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $setting = Setting::findOrFail($id);
        return view('backend.pages.setting.edit', compact('setting'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $request->validate([
            'name' => 'required',
            'nif' => 'required',
            'rc' => 'required',
            'commune' => 'required',
            'province' => 'required',
            'zone' => 'required',

            'tp_type' => 'required',
            'vat_taxpayer' => 'required',
            'ct_taxpayer' => 'required',
            'tl_taxpayer' => 'required',
            'tp_fiscal_center' => 'required',
            'tp_activity_sector' => 'required',
            'tp_legal_form' => 'required',
            'postal_number' => 'required',
            'quartier' => 'required',
            'rue' => 'required',
            'max_line' => 'required',
            'logo' => 'required|mimes:jpeg,jpg,png,svg|max:2048',

        ]);

        $setting = Setting::findOrFail($id);

        $data = $request->except(['bank_name', 'account_number']);
        $data['bank_name'] = $this->sanitizeBankArrays($request->bank_name ?? []);
        $data['account_number'] = $this->sanitizeBankArrays($request->account_number ?? []);

        if($request->hasFile('logo')){
            $file_path = "public/logo/".$setting->logo;
            Storage::delete($file_path);

            $storagepath = $request->file('logo')->store('public/logo');
            $fileName = basename($storagepath);
            $data['logo'] = $fileName;

        }

        $setting->fill($data);
        $setting->save();

        return redirect()->route('admin.settings.index');
    }

    public function postDbBackUp () {
    $now =  Carbon::now()->format("Y-m-d-H-m-i").'-backup.sql';
    try {
        Artisan::call('db:mssms',
            [
                '--database' => 'mysql',
                '--destination' => 'G:\Musumba',
                '--destinationPath' =>$now,
                '--compression' => 'gzip'
            ]
        );
    }
    catch(\Exception $e) {
        return Response::json([
            'success' => false,
            'errors' => ""
        ], 400);
    }
    return Response::json([
        'success' => true,
        'message' => 'success'
    ]);

}

    /**
     * Sanitize bank_name and account_number arrays (remove empty entries, ensure arrays).
     *
     * @param mixed $value
     * @return array
     */
    private function sanitizeBankArrays($value)
    {
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_filter(array_map('trim', $value), function ($v) {
            return $v !== '' && $v !== null;
        }));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $setting = Setting::findOrFail($id);
        $file_path = "public/logo/".$setting->logo;
            Storage::delete($file_path);
        $setting->delete();
        return redirect()->back();
    }


    public function documentation(){
        return view('backend.pages.documentation');
    }

    /**
     * Backup database and download as SQL file
     *
     * @return \Illuminate\Http\Response
     */
    public function backupDatabase()
    {
        try {
            // Get database configuration
            $host = Config::get('database.connections.mysql.host');
            $port = Config::get('database.connections.mysql.port', '3306');
            $database = Config::get('database.connections.mysql.database');
            $username = Config::get('database.connections.mysql.username');
            $password = Config::get('database.connections.mysql.password');

            // Generate backup filename with timestamp
            $filename = 'backup_' . $database . '_' . date('Y-m-d_H-i-s') . '.sql';

            // Determine mysqldump path based on OS
            $mysqldumpPath = '';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - try common paths
                $laragonBase = 'C:\\laragon\\bin\\mysql';
                $possiblePaths = [];
                
                // Check Laragon MySQL directories (scan for any MySQL version)
                if (is_dir($laragonBase)) {
                    $mysqlDirs = glob($laragonBase . '\\mysql-*', GLOB_ONLYDIR);
                    foreach ($mysqlDirs as $mysqlDir) {
                        $possiblePaths[] = $mysqlDir . '\\bin\\mysqldump.exe';
                    }
                }
                
                // Add other common paths
                $possiblePaths[] = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
                $possiblePaths[] = 'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe';
                $possiblePaths[] = 'mysqldump.exe'; // If in PATH
                
                foreach ($possiblePaths as $path) {
                    if ($path === 'mysqldump.exe') {
                        // Check if mysqldump is in PATH
                        $testCommand = 'where mysqldump.exe 2>nul';
                        exec($testCommand, $testOutput, $testReturn);
                        if ($testReturn === 0 && !empty($testOutput)) {
                            $mysqldumpPath = 'mysqldump.exe';
                            break;
                        }
                    } elseif (file_exists($path)) {
                        $mysqldumpPath = $path;
                        break;
                    }
                }
                
                // If still not found, try alternative method
                if (empty($mysqldumpPath)) {
                    return $this->backupDatabaseAlternative($database, $filename);
                }
            } else {
                // Linux/Mac - mysqldump should be in PATH
                $mysqldumpPath = 'mysqldump';
            }

            // Build mysqldump command
            $command = sprintf(
                '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s',
                escapeshellarg($mysqldumpPath),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database)
            );

            // Execute mysqldump and capture output
            $output = [];
            $returnVar = 0;
            exec($command . ' 2>&1', $output, $returnVar);

            if ($returnVar !== 0) {
                // If mysqldump failed, try alternative method using Laravel's database dump
                return $this->backupDatabaseAlternative($database, $filename);
            }

            // Combine output into SQL string
            $sql = implode("\n", $output);

            // Return file download
            return response($sql)
                ->header('Content-Type', 'application/sql')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($sql));

        } catch (\Exception $e) {
            // Fallback to alternative method
            try {
                $database = Config::get('database.connections.mysql.database');
                $filename = 'backup_' . $database . '_' . date('Y-m-d_H-i-s') . '.sql';
                return $this->backupDatabaseAlternative($database, $filename);
            } catch (\Exception $e2) {
                return redirect()->back()->with('error', __('messages.save_error') . ' ' . $e2->getMessage());
            }
        }
    }

    /**
     * Alternative backup method using Laravel's database dump
     *
     * @param string $database
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    private function backupDatabaseAlternative($database, $filename)
    {
        try {
            // Use Laravel's database dump if available (Laravel 8+)
            if (class_exists('\Illuminate\Support\Facades\Artisan')) {
                \Artisan::call('db:backup', [
                    '--database' => 'mysql',
                    '--destination' => 'local',
                    '--destinationPath' => $filename,
                ]);
                
                $backupPath = storage_path('app/backups/' . $filename);
                if (file_exists($backupPath)) {
                    return response()->download($backupPath, $filename)->deleteFileAfterSend(true);
                }
            }

            // Manual backup using PDO
            $host = Config::get('database.connections.mysql.host');
            $port = Config::get('database.connections.mysql.port', '3306');
            $username = Config::get('database.connections.mysql.username');
            $password = Config::get('database.connections.mysql.password');

            $pdo = new \PDO(
                "mysql:host={$host};port={$port};dbname={$database}",
                $username,
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $sql = "-- Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: {$database}\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            // Get all tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $sql .= "-- Table structure for table `{$table}`\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                
                $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                $sql .= $createTable['Create Table'] . ";\n\n";

                $sql .= "-- Dumping data for table `{$table}`\n";
                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
                
                if (count($rows) > 0) {
                    $columns = array_keys($rows[0]);
                    $sql .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n";
                    
                    $values = [];
                    foreach ($rows as $row) {
                        $rowValues = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $rowValues[] = 'NULL';
                            } else {
                                $rowValues[] = $pdo->quote($value);
                            }
                        }
                        $values[] = "(" . implode(', ', $rowValues) . ")";
                    }
                    $sql .= implode(",\n", $values) . ";\n\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            return response($sql)
                ->header('Content-Type', 'application/sql')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($sql));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('messages.save_error') . ' ' . $e->getMessage());
        }
    }
}
