<?php
use File;
use Excel;
use Exception;

public function importFiles()
{

        if($request->hasFile('import_file'))
        {
           
            $path = $request->file('import_file')->getRealPath();

             // is new file uploaded?
            if ($file = $request->file('import_file')) {
                $file_name = $region."-".$province."-".$ord_type."-".$timestamp_unix."-".$file->getClientOriginalName();
                $folderName = '/****/***/';
                $destinationPath = storage_path() . $folderName;
                $sourceFilePath=$path;
                $copy = \File::copy($sourceFilePath,$destinationPath.$file_name);
             }
            
            $data = Excel::load($path, function($reader) {})->get();            
            if(!empty($data) && $data->count()){
                
            // Loop through the rows in the Excel file for validation 
            foreach ($data as $key => $value) {
                    
                    $validator = Validator::make($value->all(), [
                    'sex' => 'required',
                    'surname' => 'required',
                    'first_name' => 'required',
                    'other_names' => 'required',
                    
                ]);

                  if ($validator->fails()) 
                  {
                     return redirect()->back()->withErrors($validator->errors()->all());
                  }
                    
                }

                // Fetch Existing Records
                $fetchData= DB::table('YourTable')->get();
                

                // Check for Unique Record 
                if(!empty($fetchData)){
                    unset($duplicates,$i,$j);
                for ($i = 0; $i < count($fetchData); $i++)
                    {
                        foreach ($data as $key => $value) {
                        for ($j = $i; $j < count($fetchData); $j++)
                        {
                            
                            if (($value->surname == $fetchData[$i]->surname) && ($value->first_name == $fetchData[$i]->first_name) && ($value->sex == $fetchData[$i]->sex) )
                            {
                             
                             $duplicates[] = "Duplicate Entry in your Excel file  : " .$value->surname." ".$value->first_name." | ".$value->sex." has been previously imported." ; ;

                            }
                        }
                       }
                    }
             }
             if(!empty($duplicates))
             {
                Flash::error('Remove Duplicates in your Excel file.');
                 return redirect()->back()->withErrors(array_unique($duplicates));
            }

            // Loop through the rows in the Excel file to set/store value into the $insert array 
            $line_number = 0;
            foreach ($data as $key => $value) {

                       $line_number = $line_number + 1;

                                    
            $insert[] = [
                        'line' => $line_number,
                        'sex' => $value->sex,
                        'surname' => $value->surname,
                        'last_name' => $value->surname,
                        'first_name' => $value->first_name,
                        'other_names' => $value->other_names,
                        
                        ];  
            }

                if(!empty($insert)){
                
                foreach ($insert as $insert_data) {

               try{
                   
                    $new_insert = OrdineePersonalDetails::create($insert_data);
                   
                }
                 catch(Exception $exception)
                    {
                        $file_line = $insert_data['line'];
                        $file_line_b4 = $file_line - 1;
                        $error_bag=json_encode($exception->errorInfo[2]);
                        $error_msg1 = "The import process was interupted because of the error encountered below on  *Line Number $file_line* of your Excel file";
                         $error_msg2 = $error_bag." :: The TRCCG CODE on the Line Number $file_line of your excel file. It belongs to someone else.  ";
                        if($file_line <= 1) {
                            $error_msg3 = "";
                        } elseif($file_line == 2) {
                            $error_msg3 = "However, the data on *Line 1* has been imported successfully";
                        } elseif($file_line > 2) {
                             $error_msg3 = "However, the data on the lines before the error (i.e. From Line 1 - $file_line_b4) has been imported successfully";
                        } else {
                            $error_msg3 = "";
                        }

                       
                        $errormsg[] =  [
                                        'error_msg1' => $error_msg1,
                                        'error_msg2' => $error_msg2,
                                        'error_msg3' => $error_msg3,
                                        'line' => $file_line,
                                        ]; 
                        return back()->with('exception',$errormsg);
            
                    }

        
              } 
                    $count = count($insert);
                    $successmsg = "$count Records Imported successfully ";
                    return back()->with('success',$successmsg);
                }          
   
            }
   } 
} 
?>
