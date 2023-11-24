<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
// use App\Models\Users;
use App\Models\EdgeTypes;
// use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

// require_once 'vendor/autoload.php';

// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ScenarioController extends Controller
{
    //Get Per User Scenario Count
    public function getPerUserScenarios(Request $request)
    {
        // $userId = auth()->user()->id;
        $userId = $request->user_id;
        // $email = $request->email;
        // $password = md5($request->password);
        $sql = "select count(*) as count FROM scenarios as s WHERE s.deleted = 0 and s.user_id =".$userId;
        // echo $sql;
        $result = DB::connection('pgsql2')->select($sql);
        // print_r($result);
        
        // $result = DB::select($sql);
        return response()->json([
            'totalScenariosPerUser' => $result
        ]);
    }

    //Add Scenario
    public function addUserScenario(Request $request){

        // ob_end_clean(); // this
        // ob_start(); // and this

        // Checked condition when we insert into filter criteria into the user_filter_criterias table
        // if (!('multidelete' in Object.prototype)) {
        //   Object.defineProperty(Object.prototype, 'multidelete', {
        //     value: function () {
        //       for (var i = 0; i < arguments.length; i++) {
        //         delete this[arguments[i]];
        //       }
        //     }
        //   });
        // }
        // delete scenario.filter_criteria["single_ta_id"];
      
        // if (parseInt(scenario.page_id) == 7) {
        //   scenario.filter_criteria.multidelete('ta_id', 'di_ids', 'single_ta_id', 'ct_di_ids');
        // } else if (parseInt(scenario.page_id) == 1 || parseInt(scenario.page_id) == 2 || parseInt(scenario.page_id) == 4 || parseInt(scenario.page_id) == 5 || parseInt(scenario.page_id) == 8 || parseInt(scenario.page_id) == 9 || parseInt(scenario.page_id) == 10 || parseInt(scenario.page_id) == 11) {
        //   scenario.filter_criteria.multidelete('ta_id_dashboard', 'di_ids_dashboard', 'single_ta_id', 'ct_di_ids');
        // } else if (parseInt(scenario.page_id) == 3) {
        //   scenario.filter_criteria.multidelete('ta_id_dashboard', 'di_ids_dashboard', 'ta_id', 'di_ids', 'single_ta_id');
        // }
        //End here
      
        $scenario = $request;
        // echo "scenario1: ", $scenario;
        // echo "scenario2: ", $scenario->user_id['user_id'];

        $sql = "INSERT INTO scenarios (user_id,scenario_name,filter_criteria, comments) 
        values ('".$scenario->user_id['user_id']."','".$scenario->filter_name."','".json_encode(($scenario->filter_criteria))."','".$scenario->user_comments."')";
        // echo $sql;
        
        $result = DB::connection('pgsql2')->select($sql);
        $lastId = DB::connection('pgsql2')->getPdo()->lastInsertId(); // get the last inserted id

        //Start Result set is also stored in the excel format 
        if($scenario->result_set_checked == true)
        {
            // $product_new = json_encode($scenario['result_data_set']);
            // $products = json_decode($product_new, true);            
            
            $csvFileName = $scenario->filter_name.".csv";
            $path = storage_path('app/public/'.$scenario->user_id['user_id']);
            // $path = storage_path('app/public/');            
            $file = fopen($path.$csvFileName, 'w');
            $columns = array('news_id', 'sourcenode', 'destinationnode','level','PMIDCount');
            fputcsv($file, $columns);
            foreach ($scenario['result_data_set'] as $product) {
                $row['news_id']  = $product['news_id'];
                $row['sourcenode']  = $product['sourcenode'];
                $row['destinationnode']  = $product['destinationnode'];
                $row['level']  = $product['level'];
                $row['PMIDCount']  = $product['PMIDCount'];                
                fputcsv($file, array($row['news_id'], $row['sourcenode'], $row['destinationnode'], $row['level'], $row['PMIDCount'] ));
            }

            fclose($file);
            // Excel::store($products, $csvFileName, 'public');
            // $filePath = Storage::url("storage/{$csvFileName}");
            // $path = storage_path($filePath);

            $csvFileNameWithPath = $path.$csvFileName;
            $csvFileNameExtension = pathinfo($csvFileNameWithPath, PATHINFO_EXTENSION);

            // $target = 'advisor/short_videos/'.md5(uniqid()).'_'.time().".".$csvFileNameExtension;//creating complete file name        
            $target = md5(uniqid()).'_'.time().".".$csvFileNameExtension;//creating complete file name        
            Storage::disk('s3')->put($target, fopen($csvFileNameWithPath, 'r+'));//uploading video into S3 bucket
            $s3FileName = Storage::disk('s3')->url( $target );//getting URL of uloaded video from S3
            // return $s3FileName;
            
            $sql = "UPDATE scenarios SET uploaded_file_url='".$s3FileName."' where id='".$lastId."' and user_id = '".$scenario->user_id['user_id']."' ";
            // echo $sql;

            //After inserting the excel file url into database delete the file from folder
            unlink($csvFileNameWithPath);
            
            $result = DB::connection('pgsql2')->select($sql);
            return response()->json([
                'scenarioUpdate' => $result
            ]);
        }else{
            return response()->json([
                'scenarioUpdate' => $result
            ]);
        }       
    }

    //Update Scenario
    public function updateUserScenario(Request $request){            
        $scenario = $request;
        $fileUrl = $scenario->fileName;

        ////////// CODE here to upload the excel file into S3 Bucket HERE //////////////////
        // $file_upload = $request->file('files');
        // foreach ($file_upload as $file) {
        //     $path = $file->store('bucket', 's3');
        //     $fileUrl = File::create([
        //         'filename' => basename($path),
        //         'url' => Storage::disk('s3')->url($path)
        //     ]);
        // }
        // return $fileUrl;

        // $request->validate([
        //     'file' => 'required|mimes:doc,csv,txt|max:2048',
        // ]); 
        // $fileName = $request->file->getClientOriginalName();
        // $fileName = $fileUrl->getClientOriginalName();
        // $filePath = 'uploads/' . $fileName; 
        // $path = Storage::disk('s3')->put($filePath, file_get_contents($fileUrl));
        // $path = Storage::disk('s3')->url($path);
        // echo $path;

        // Perform the database operation here 
        // return back()
        //     ->with('success','File has been successfully uploaded.');


        $sql = "UPDATE scenarios SET uploaded_file_url='".$fileUrl."' where id='".$scenario->scenario_id."' and user_id = '".$scenario->user_id['user_id']."' ";
        // echo $sql;
        
        $result = DB::connection('pgsql2')->select($sql);
        return response()->json([
            'scenarioUpdate' => $result
        ]);
    }

    // Get User Scenario
    public function getUserScenarios(Request $request){
        $userId = $request->user_id;
        $sql = "select u.user_name, s.id, s.user_id, s.scenario_name, s.filter_criteria, s.uploaded_file_url, s.comments, s.created_at FROM scenarios as s LEFT JOIN users as u on s.user_id=u.user_id 
        WHERE s.deleted = 0 and s.user_id =".$userId." order by created_at desc";
        // echo $sql;
        $result = DB::connection('pgsql2')->select($sql);
        return response()->json([
            'scenarios' => $result
        ]);

    }

    // Delete User Scenario
    public function delUserScenario(Request $request){
        if ($request->scenario_id != "undefined")
            $scenarioID = $request->scenario_id;
        else
            $scenarioID = 0;

        $userId = $request->user_id;

        //Start to delete the file from S3 bucket
        $sql = "select s.uploaded_file_url FROM scenarios as s WHERE s.id = ".$scenarioID." and s.user_id =".$userId;        
        $result = DB::connection('pgsql2')->select($sql);        
        
        // $uploaded_file_url = "";
        if (count($result) > 0) {
            foreach ($result as $value) {
                $uploaded_file_url = $value->uploaded_file_url;
            }
        }
        // echo "url: ".$uploaded_file_url;

        $s3_filename = basename($uploaded_file_url);
        Storage::disk('s3')->delete($s3_filename);
        //End to delete the file from S3 bucket

        // $sql = "UPDATE scenarios set deleted = 1 where id=" . $scenarioID . " and user_id =".$userId;
        $sql = "DELETE FROM scenarios where id=" . $scenarioID . " and user_id =".$userId;
        // echo $sql;
        $result = DB::connection('pgsql2')->select($sql);
        return response()->json([
            'scenariosDel' => $result
        ]);
    }

    // Get User Scenario
    public function getUserArticleSentencesDashboard(Request $request){
        $userId = $request->user_id;
        $sql = "select u.user_name, s.id, s.user_id, s.name, s.resultset, s.uploaded_file_url, s.description, s.created_at FROM article_sentences_dashboard as s LEFT JOIN users as u on s.user_id=u.user_id 
        WHERE s.deleted = 0 and s.user_id =".$userId." order by created_at desc";
        // echo $sql;
        $result = DB::connection('pgsql2')->select($sql);
        return response()->json([
            'scenarios' => $result
        ]);
    }
    
    // Delete User Scenario
    public function delArticleSentencesScenario(Request $request){
        if ($request->scenario_id != "undefined")
            $scenarioID = $request->scenario_id;
        else
            $scenarioID = 0;

        $userId = $request->user_id;

        //Start to delete the file from S3 bucket
        $sql = "select s.uploaded_file_url FROM article_sentences_dashboard as s WHERE s.id = ".$scenarioID." and s.user_id =".$userId;        
        $result = DB::connection('pgsql2')->select($sql);        
        
        // $uploaded_file_url = "";
        if (count($result) > 0) {
            foreach ($result as $value) {
                $uploaded_file_url = $value->uploaded_file_url;
            }
        }
        // echo "url: ".$uploaded_file_url;

        $s3_filename = basename($uploaded_file_url);
        Storage::disk('s3')->delete($s3_filename);
        //End to delete the file from S3 bucket

        // $sql = "UPDATE article_sentences_dashboard set deleted = 1 where id=" . $scenarioID . " and user_id =".$userId;
        $sql = "DELETE FROM article_sentences_dashboard where id=" . $scenarioID . " and user_id =".$userId;
        // echo $sql;
        $result = DB::connection('pgsql2')->select($sql);
        return response()->json([
            'scenariosDel' => $result
        ]);
    }

}