<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Horario;
use Illuminate\Http\Request;
use App\Mail\ConfirmacionCita;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use PDF;
use Illuminate\Support\Str;


class CitaController extends Controller
{
    public function agendarCita(Request $request)
    {
        $exito = false;

        

        DB::beginTransaction();
        try {
            $cita = new Cita;
            // $cita->folio = 'FDEA3C';
            $cita->folio = '';
            $cita->fecha_cita = $request->dia_cita;
            $cita->fecha_formateada = $request->fecha_formateada;
            $cita->hora_cita = $request->horario;
            $cita->nombre = $request->nombre;
            $cita->email = $request->email;
            $cita->telefono = $request->telefono;
            $cita->sexo = $request->sexo;
            $cita->tiene_discapacidad = $request->tiene_discapacidad == 'Si' ? true : false;
            $cita->tramite_id = $request->tramite;
            $cita->centro_atencion_id = $request->centro_atencion;

            
            $cita->save();

            $tipoTramite = $cita->tramite->tipoTramite->id;

            // return response()->json([
            //     "status" => "not-found",
            //     "message" => "sdfsdfsdfs.",
            //     "tramite" => $tipoTramite
            // ], 200);
            
            $folio = '';

            switch ($tipoTramite) {
                case '1':
                    if($cita->id < 10)
                    {
                        $folio = $folio."ACF000".$cita->id;
                    }
                    if($cita->id > 10 && $cita->id < 100)
                    {
                        $folio = $folio."ACF00".$cita->id;
                    }
                    if($cita->id > 100 && $cita->id < 1000)
                    {
                        $folio = $folio."ACF0".$cita->id;
                    }
                    if($cita->id > 1000)
                    {
                        $folio = $folio."ACF".$cita->id;
                    }
                    break;

                case '2': 
                    if($cita->id < 10)
                    {
                        $folio = $folio."AA000".$cita->id;
                    }
                    if($cita->id > 10 && $cita->id < 100)
                    {
                        $folio = $folio."AA00".$cita->id;
                    }
                    if($cita->id > 100 && $cita->id < 1000)
                    {
                        $folio = $folio."AA0".$cita->id;
                    }
                    if($cita->id > 1000)
                    {
                        $folio = $folio."AA".$cita->id;
                    }
                    break;
                case '3': 

                    if($cita->id < 10)
                    {
                        $folio = $folio."AL000".$cita->id;
                    }
                    if($cita->id > 10 && $cita->id < 100)
                    {
                        $folio = $folio."AL00".$cita->id;
                    }
                    if($cita->id > 100 && $cita->id < 1000)
                    {
                        $folio = $folio."AL0".$cita->id;
                    }
                    if($cita->id > 1000)
                    {
                        $folio = $folio."AL".$cita->id;
                    }
                    break;

                case '4':
                    
                    if($cita->id < 10)
                    {
                        $folio = $folio."ES000".$cita->id;
                    }
                    if($cita->id > 10 && $cita->id < 100)
                    {
                        $folio = $folio."ES00".$cita->id;
                    }
                    if($cita->id > 100 && $cita->id < 1000)
                    {
                        $folio = $folio."ES0".$cita->id;
                    }
                    if($cita->id > 1000)
                    {
                        $folio = $folio."ES".$cita->id;
                    }
                    break;
            }

            $cita->folio = $folio;
            $cita->save();

            $horario = Horario::find($request->hora_cita);
            $horario->citas_disponibles = $horario->citas_disponibles-1;
            $horario->save(); 
        
            $citaAgendada = new \stdClass();
            $citaAgendada->id = $cita->id;
            $citaAgendada->folio = $cita->folio;
            $citaAgendada->nombre = $cita->nombre;
            $citaAgendada->tramite = $cita->tramite->nombre;
            $citaAgendada->fecha = $cita->fecha_formateada;
            $citaAgendada->hora = $cita->hora_cita;
            $citaAgendada->centro_atencion = $cita->centroAtencion->nombre;
            $citaAgendada->direccion_centro_atencion = $cita->centroAtencion->direccion;

            // $pdf = $this->generarArchivoCitaPdf();
            // $pdf = '';
            // $pdf = app('App\Http\Controllers\CitaController')->generarArchivoCitaPdf();

            //Custom Header
            PDF::setHeaderCallback(function($pdf) {

                // $pdf->SetY(10);

                // Set font
                $pdf->SetFont('helvetica', 'B', 11);


                // Header
                $header_image_file = public_path() . '/images/header_pdf.png';           
                $pdf->Image($header_image_file, 0,0,0,0);

            });

            
           // Custom Footer
            PDF::setFooterCallback(function($pdf) {
                
                // Position at 15 mm from bottom
                // $pdf->SetY(-15);
                // Set font
                $pdf->SetFont('helvetica', 'I', 8);

                $style = array(
                    // 'border' => 2,
                    'vpadding' => 'auto',
                    'hpadding' => 'auto',
                    'fgcolor' => array(0,0,0),
                    'bgcolor' => false, //array(255,255,255)
                    'module_width' => 1, // width of a single module in points
                    'module_height' => 1 // height of a single module in points
                ); 

                $pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,Q', 160, 200, 70, 70, $style, 'N');

                // footer
                $footer_image_file = public_path() . '/images/footer_pdf.png';
                $pdf->Image($footer_image_file, 0,280,0,0);
                // Page number
                //$pdf->Cell(0, 10, 'Página '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');

            });

            // $style = array(
            //     'border' => 2,
            //     'vpadding' => 'auto',
            //     'hpadding' => 'auto',
            //     'fgcolor' => array(0,0,0),
            //     'bgcolor' => false, //array(255,255,255)
            //     'module_width' => 1, // width of a single module in points
            //     'module_height' => 1 // height of a single module in points
            // );

            $view = View::make('pdf.pdf_confirmacion_cita', compact('citaAgendada'));
            $html_content = $view->render();

            PDF::SetTitle('Confirmación Cita');
            
            PDF::AddPage('P', 'A4');
            // PDF::write2DBarcode('www.tcpdf.org', 'QRCODE,H', 20, 210, 50, 50, $style, 'N');
            // PDF::Text(20, 205, 'QRCODE H');

            PDF::writeHTML($html_content, true, false, true, false, '');

            ob_end_clean();
            
            $pdf = PDF::Output('Confirmacion_Cita.pdf', 'S');

            Mail::to($cita->email)->send(new ConfirmacionCita($citaAgendada, $pdf));

            DB::commit();
            $exito = true;
        } catch (\Throwable $th) {
            DB::rollback();
            $exito = false;
            return response()->json([
                "status" => "error",
                "message" => "Ocurrió un error al agendar la cita.",
                "error" => $th->getMessage(),
                "location" => $th->getFile(),
                "line" => $th->getLine(),
            ], 200);
        }

        if ($exito) {
            return response()->json([
                "status" => "ok",
                "message" => "Cita agendada con exito.",
                "cita_agendada" => $citaAgendada,
            ], 200);
        }
    }

    public function generarArchivoCitaPdf()
    {
        // Custom Header
        PDF::setHeaderCallback(function($pdf) {

            $pdf->SetY(10);

            // Set font
            $pdf->SetFont('helvetica', 'B', 11);
            // Header
            // $header_image_file = asset('/img/pages/fichapdf/header.svg');            
            // $pdf->Image($header_image_file, 12,10,188,30);

        });

        
        // Custom Footer
        PDF::setFooterCallback(function($pdf) {
            
            // Position at 15 mm from bottom
            $pdf->SetY(-15);
            // Set font
            $pdf->SetFont('helvetica', 'I', 8);
            // footer
            // $footer_image_file = asset('/img/pages/fichapdf/footer.svg');            
            // $pdf->Image($footer_image_file, 12,270,188,17);
            // Page number
            //$pdf->Cell(0, 10, 'Página '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');

        });

        $view = View::make('pdf.pdf_confirmacion_cita');
        $html_content = $view->render();

        PDF::SetTitle('Confirmación Cita');
        
        PDF::AddPage('P', 'A4');

        PDF::writeHTML($html_content, true, false, true, false, '');

        ob_end_clean();
        
        // $ficha_registro = PDF::Output(uniqid().'_Ficha.pdf', 'S');
        // return $ficha_registro;
        // PDF::Output(public_path($filename), 'I');
        PDF::Output(uniqid().'_Ficha.pdf', 'I');
    }

    public function buscarCita(Request $request)
    {
        try {
            $cita = Cita::where('folio', $request->folio)->where('status', 1)->first();

            if ($cita) {
                $citaAgendada = new \stdClass();
                $citaAgendada->id = $cita->id;
                $citaAgendada->folio = $cita->folio;
                $citaAgendada->nombre = $cita->nombre;
                $citaAgendada->tramite = $cita->tramite->nombre;
                $citaAgendada->fecha = $cita->fecha_formateada;
                $citaAgendada->hora = $cita->hora_cita;
                $citaAgendada->centro_atencion = $cita->centroAtencion->nombre;
                $citaAgendada->direccion_centro_atencion = $cita->centroAtencion->direccion;

                return response()->json([
                    "status" => "ok",
                    "message" => "Cita encontrada con exito",
                    "cita" => $citaAgendada
                ], 200);
            } else {
                return response()->json([
                    "status" => "not-found",
                    "message" => "No se encontro ninguna cita registrada con ese número de folio.",
                ], 200);
            }

        } catch (\Throwable $th) {
            DB::rollback();
            $exito = false;
            return response()->json([
                "status" => "error",
                "message" => "Ocurrió un error al buscar cita.",
                "error" => $th->getMessage(),
                "location" => $th->getFile(),
                "line" => $th->getLine(),
            ], 200);
        }
    }

    public function cancelarCita($id)
    {
        try {
            $cita = Cita::find($id);
            $cita->status = 3;
            $cita->save();

            $horario = $cita->centroAtencion->dias->where('fecha',$cita->fecha_cita)->first()->horarios->where('hora_inicio',$cita->hora_cita)->first();

            $horario->citas_disponibles++;
            $horario->save();


            // return response()->json([
            //     "status" => "ok",
            //     "message" => "Cita cancelada con exito",
            //     "horario" => $horario
            // ], 500);

            return response()->json([
                "status" => "ok",
                "message" => "Cita cancelada con exito",
            ], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            $exito = false;
            return response()->json([
                "status" => "error",
                "message" => "Ocurrió un error al buscar cita.",
                "error" => $th->getMessage(),
                "location" => $th->getFile(),
                "line" => $th->getLine(),
            ], 200);
        }
    }

    public function imprimirCita($id)
    {
        $cita = Cita::find($id);

        $citaAgendada = new \stdClass();
        $citaAgendada->id = $cita->id;
        $citaAgendada->folio = $cita->folio;
        $citaAgendada->nombre = $cita->nombre;
        $citaAgendada->tramite = $cita->tramite->nombre;
        $citaAgendada->fecha = $cita->fecha_formateada;
        $citaAgendada->hora = $cita->hora_cita;
        $citaAgendada->centro_atencion = $cita->centroAtencion->nombre;
        $citaAgendada->direccion_centro_atencion = $cita->centroAtencion->direccion;

        // Custom Header
        PDF::setHeaderCallback(function($pdf) {

            // $pdf->SetY(10);

            // Set font
            $pdf->SetFont('helvetica', 'B', 11);
            // Header
            $header_image_file = public_path() . '/images/header_pdf.png';           
            $pdf->Image($header_image_file, 0,0,0,0);

        });

        
        // Custom Footer
        PDF::setFooterCallback(function($pdf) {

            $style = array(
                // 'border' => 2,
                'vpadding' => 'auto',
                'hpadding' => 'auto',
                'fgcolor' => array(0,0,0),
                'bgcolor' => false, //array(255,255,255)
                'module_width' => 1, // width of a single module in points
                'module_height' => 1 // height of a single module in points
            );
            
            $pdf->write2DBarcode('http://defensoria_publica.test', 'QRCODE,Q', 160, 200, 70, 70, $style, 'N');
            // $pdf->Text(20, 145, 'QRCODE Q');

            // Position at 15 mm from bottom
            // $pdf->SetY(-15);
            // Set font
            $pdf->SetFont('helvetica', 'I', 8);
            // footer
            $footer_image_file = public_path() . '/images/footer_pdf.png';
            $pdf->Image($footer_image_file, 0,280,0,0);
            // Page number
            //$pdf->Cell(0, 10, 'Página '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');

        });

        $view = View::make('pdf.pdf_confirmacion_cita', compact('citaAgendada'));
        $html_content = $view->render();

        PDF::SetTitle('Confirmación Cita');
        
        PDF::AddPage('P', 'A4');

        PDF::writeHTML($html_content, true, false, true, false, '');

        ob_end_clean();
        
        // $ficha_registro = PDF::Output(uniqid().'_Ficha.pdf', 'S');
        // return $ficha_registro;
        // PDF::Output(public_path($filename), 'I');
        PDF::Output(uniqid().'_Ficha.pdf', 'I');

        // return response()->json([
        //     "asdf" => "aaaaa",
        //     "re" => $cita
        // ], 200);
    }
}