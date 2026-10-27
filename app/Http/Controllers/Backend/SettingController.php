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
     * Backup database and download as a compressed ZIP file.
     *
     * @return \Illuminate\Http\Response
     */
    public function backupDatabase()
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        try {
            $host = Config::get('database.connections.mysql.host');
            $port = Config::get('database.connections.mysql.port', '3306');
            $database = Config::get('database.connections.mysql.database');
            $username = Config::get('database.connections.mysql.username');
            $password = Config::get('database.connections.mysql.password');

            $filename = 'backup_' . $database . '_' . date('Y-m-d_H-i-s') . '.sql';
            $sqlPath = $this->backupDirectory() . DIRECTORY_SEPARATOR . $filename;

            $mysqldumpPath = $this->resolveMysqldumpPath();
            if (empty($mysqldumpPath)) {
                return $this->backupDatabaseAlternative($database, $filename);
            }

            $command = sprintf(
                '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers --result-file=%s %s',
                escapeshellarg($mysqldumpPath),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($sqlPath),
                escapeshellarg($database)
            );

            $output = [];
            $returnVar = 0;
            exec($command . ' 2>&1', $output, $returnVar);

            if ($returnVar !== 0 || !is_file($sqlPath) || filesize($sqlPath) === 0) {
                @unlink($sqlPath);
                return $this->backupDatabaseAlternative($database, $filename);
            }

            return $this->downloadSqlAsZip($sqlPath, $filename);
        } catch (\Exception $e) {
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
     * Alternative backup method using Laravel's database dump or PDO.
     *
     * @param string $database
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    private function backupDatabaseAlternative($database, $filename)
    {
        try {
            if (class_exists('\Illuminate\Support\Facades\Artisan')) {
                try {
                    \Artisan::call('db:backup', [
                        '--database' => 'mysql',
                        '--destination' => 'local',
                        '--destinationPath' => $filename,
                    ]);

                    $backupPath = storage_path('app/backups/' . $filename);
                    if (file_exists($backupPath)) {
                        return $this->downloadSqlAsZip($backupPath, $filename);
                    }
                } catch (\Exception $ignored) {
                    // Fallback to PDO dump below.
                }
            }

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

            $sqlPath = $this->backupDirectory() . DIRECTORY_SEPARATOR . $filename;
            $handle = fopen($sqlPath, 'w');
            if ($handle === false) {
                throw new \RuntimeException('Impossible de créer le fichier SQL temporaire.');
            }

            fwrite($handle, "-- Database Backup\n");
            fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Database: {$database}\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                fwrite($handle, "-- Table structure for table `{$table}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

                $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                fwrite($handle, $createTable['Create Table'] . ";\n\n");

                fwrite($handle, "-- Dumping data for table `{$table}`\n");
                $rows = $pdo->query("SELECT * FROM `{$table}`");
                $written = 0;

                while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                    if ($written === 0) {
                        fwrite($handle, "INSERT INTO `{$table}` (`" . implode('`, `', array_keys($row)) . "`) VALUES\n");
                    } else {
                        fwrite($handle, ",\n");
                    }

                    $rowValues = [];
                    foreach ($row as $value) {
                        $rowValues[] = $value === null ? 'NULL' : $pdo->quote($value);
                    }
                    fwrite($handle, "(" . implode(', ', $rowValues) . ")");
                    $written++;
                }

                if ($written > 0) {
                    fwrite($handle, ";\n\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            return $this->downloadSqlAsZip($sqlPath, $filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('messages.save_error') . ' ' . $e->getMessage());
        }
    }

    /**
     * Compress the SQL dump into a ZIP archive and download it.
     *
     * @param string $sqlPath
     * @param string $sqlFilename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private function downloadSqlAsZip($sqlPath, $sqlFilename)
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('L\'extension PHP ZipArchive est requise pour compresser la sauvegarde.');
        }

        $zipFilename = preg_replace('/\.sql$/i', '', $sqlFilename) . '.zip';
        $zipPath = $this->backupDirectory() . DIRECTORY_SEPARATOR . $zipFilename;

        $zip = new \ZipArchive();
        $opened = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($opened !== true) {
            @unlink($sqlPath);
            throw new \RuntimeException('Impossible de créer le fichier ZIP de sauvegarde.');
        }

        $zip->addFile($sqlPath, $sqlFilename);
        $zip->close();
        @unlink($sqlPath);

        return response()->download($zipPath, $zipFilename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @return string
     */
    private function backupDirectory()
    {
        $directory = storage_path('app/backups');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory;
    }

    /**
     * @return string
     */
    private function resolveMysqldumpPath()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return 'mysqldump';
        }

        $possiblePaths = [];
        $laragonBase = 'C:\\laragon\\bin\\mysql';
        if (is_dir($laragonBase)) {
            $mysqlDirs = glob($laragonBase . '\\mysql-*', GLOB_ONLYDIR);
            foreach ($mysqlDirs as $mysqlDir) {
                $possiblePaths[] = $mysqlDir . '\\bin\\mysqldump.exe';
            }
        }

        $possiblePaths[] = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        $possiblePaths[] = 'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe';
        $possiblePaths[] = 'mysqldump.exe';

        foreach ($possiblePaths as $path) {
            if ($path === 'mysqldump.exe') {
                exec('where mysqldump.exe 2>nul', $testOutput, $testReturn);
                if ($testReturn === 0 && !empty($testOutput)) {
                    return 'mysqldump.exe';
                }
                continue;
            }

            if (file_exists($path)) {
                return $path;
            }
        }

        return '';
    }
}
