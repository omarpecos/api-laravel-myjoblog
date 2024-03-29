<?php

namespace App\Http\Controllers;

use App\Export;
use App\Journey;
use App\Helpers\JwtAuth;
use Illuminate\Http\Request;
use Spipu\Html2Pdf\Html2Pdf;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Storage;

class PdfController extends ApiController
{
    /* Function de prueba para generar un PDF 
    public function print_pdf(){

        // recoger los datos a pintar
        $journeys = Journey::find([36, 37, 38]);
    

    //recoger el contenido del otro fichero
        $content = view('print_view',
                ['journeys' => $journeys])->render();
        
        $html2pdf = new Html2Pdf('P','A4','es','true','UTF-8');
        //$html2pdf->output('pdf_generated.pdf');


        $html2pdf->WriteHTML($content);
        
        $time = date('H:m:i',time());
        
    //dd(storage_path('app/public/images'));
        $pdf= $html2pdf->Output(storage_path('storage/app/pdf/').'pdf_time_'.$time.'.pdf', 'F'); // The filename is ignored when you use 'S' as the second parameter.

        echo "Pdf generado de time ".$time."!!! ";
                //respuesta para visualizarlo !!!
                    // return response($pdf)
                                 // ->header('Content-Type', 'application/pdf')
                                  //->header('Content-Length', strlen($pdf))
                                  //->header('Content-Disposition', 'inline; filename="example.pdf"');
                
        }

    */


    public function getTrigger(Request $request){

        $hash = $request->header('Authorization',null);
        $JwtAuth = new JwtAuth();
        $checkToken = $JwtAuth->checkToken($hash);

        if ($checkToken){
             $user = $JwtAuth->checkToken($hash,true);

             $info = DB::table('trigger_pdf')->where('user_id',$user->sub)->first();

             $info->id_journeys = json_decode($info->id_journeys,true);

              return response()->json(['status'=>'success','trigger'=>$info],200);

            }else{

            return $this->errorResponse('No autenticado',401);
        } 

    }

    public function setTrigger(Request $request){

        $hash = $request->header('Authorization',null);
        $JwtAuth = new JwtAuth();
        $checkToken = $JwtAuth->checkToken($hash);

        if ($checkToken){
             $user = $JwtAuth->checkToken($hash,true);

                   //Recoger post
                    $json = $request->input('json',null);
                    $params = json_decode($json);

        //return response()->json($params->id_journeys,200);

                    $info = DB::table('trigger_pdf')
                            ->where('user_id',$user->sub)
                             ->update(['quantity' => $params->quantity,
                                        'id_journeys'=> json_encode($params->id_journeys,JSON_FORCE_OBJECT)
                                    ]);

                    return response()->json(['status'=>'success','updated'=>$info],200);

            }else{

            return $this->errorResponse('No autenticado',401);
        } 

    }

    public function getMyFiles(Request $request){
        $hash = $request->header('Authorization',null);
        $JwtAuth = new JwtAuth();
        $checkToken = $JwtAuth->checkToken($hash);

        if ($checkToken){
            $user = $JwtAuth->checkToken($hash,true);
            $type = $request->input('type',null);
            
            // sacar los nombre de la BDD
               // $names = DB::table('exports')->where('user_id',$user->sub)->get();

            if ($type == 'auto'){
                $names = Export::where('user_id',$user->sub)
                        ->where('type',$type)
                        ->get();
            }
            else{
                 $names = Export::where('type',$type)
                        ->get();
            }
            
              //return response()->json(['status'=>'success','files'=>$names],200);
                return $this->showAll($names,'files');

            }else{

            return $this->errorResponse('No autenticado',401);
        } 
    }

    public function seeFile(Request $request){

            /*$hash = $request->header('Authorization',null);
            $JwtAuth = new JwtAuth();
            $checkToken = $JwtAuth->checkToken($hash);

            if ($checkToken){

                     //$user = $JwtAuth->checkToken($hash,true);*/

                     $name = $request->name;
                     //$pdf = Storage::disk('local')->get('pdf/'.$name);
                     $path = storage_path("app/pdf/".$name);
                   
                   
                    /* return response($pdf)
                                          ->header('Content-Type', 'application/pdf')
                                          ->header('Content-Length', strlen($pdf))
                                          ->header('Content-Disposition', 'inline; filename="djhjhgjj"');*/

                    return response()->file($path);   
           /* }else{

                 return $this->errorResponse('No autenticado',401);
        }*/
            
        }
    public function downloadFile(Request $request){
            /*$hash = $request->header('Authorization',null);
            $JwtAuth = new JwtAuth();
            $checkToken = $JwtAuth->checkToken($hash);

            if ($checkToken){*/
                 $name = $request->name;
                 $path = storage_path("app/pdf/".$name);

                return response()->download($path, $name);
           /*}else{

                 return $this->errorResponse('No autenticado',401);
            }*/

    }
    public function deleteFile(Request $request){

        $hash = $request->header('Authorization',null);
        $JwtAuth = new JwtAuth();
        $checkToken = $JwtAuth->checkToken($hash);

        if ($checkToken){
                 $usuario = $JwtAuth->checkToken($hash,true);
              $name = $request->name;
             //$path = storage_path("app/pdf/".$name);xxx


              // segun el export de tal namefile -> att -> content (arrya con las jornadas)
              $exports = Export::where('namefile', '=', $name)->get();
              $export = $exports->first();

              if ($export->type == 'generated'){
                    $id_jornadas = json_decode($export->content,true);

                  // -> saca las jornadas
                  $jornadas = Journey::find($id_jornadas);
                  // recorre las jornadas y elimina sus firmas y las jornadas
                  if (count($jornadas) > 0){
                        foreach ($jornadas as $j) {
                             Storage::disk('images')->delete($j->signature);
                             $j->delete();
                        }
                  }
              }
              

              // luego delete el export y el file en server
                // $export->delete();
              foreach ($exports as $e) {
                             $e->delete();
                        }
             Storage::disk('local')->delete('pdf/'.$name);

             $now = time();
             $fechayhora = date("Y-m-d H:i:s",$now);
             DB::statement('call regaccion(?,?,?,?,?)',array($usuario->sub,$usuario->name.' '.$usuario->surname,$usuario->role,'Borrado del pdf -  '.$name,$fechayhora));

             return response()->json([
                'status' => 'success',
                'deletedexport' => true
             ]);

         }else{
                 return $this->errorResponse('No autenticado',401);
        } 

        
    }

    /* public function descargarfile(Request $request){
       
      // $path = storage_path($request->input("file"));
       $path = $request->input('file');
    
      // return response()->download($path);
       return Storage::download($path);
        
         //route('home')->with(["message" =>'Excel descargado correctamente']);
   }*/

}