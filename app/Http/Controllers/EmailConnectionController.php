<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Normalizer;
use Webklex\IMAP\Facades\Client;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class EmailConnectionController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $subject = $this->normaliza_texto('Carta invitación, Diplomado en línea');
        $pdf_name = $this->normaliza_texto('carta invitación');
        $pdf_extension = 'pdf';
        $save_path = storage_path('app/public/');
        $success_flag = 0;
        try {
            //  Conexion con el servidor de gmail mediante la configuracion de .env
            $client = Client::account();
            $client->connect();
            //  Obtencion de los email
            $folder = $client->getFolder('INBOX');
            $messages = $folder->query()
                ->since(now()->subDays(7))
                ->get();
            foreach ($messages as $message) {
                //  Normalizacion de texto para las comparaciones
                $clean_tmp_subject = $this->normaliza_texto($message->getSubject());
                if (str_contains($clean_tmp_subject, $subject)) {
                    foreach ($message->getAttachments() as $attachment) {
                        $filename = $attachment->getName();
                        //  Comparacion de nombres de los pdf
                        $clean_filename = $this->normaliza_texto($filename);
                        $extension = pathinfo($clean_filename, PATHINFO_EXTENSION);
                        if ($extension==$pdf_extension){
                            //  Guardado de archivo
                            if (str_contains($clean_filename, $pdf_name)) {
                                $attachment->save($save_path, $filename);
                                $full_path = $save_path . '/' . $filename;
                                //  PDF a TXT
                                $parser = new Parser();
                                $pdf = $parser->parseFile($full_path);
                                $text = $pdf->getText();
                                
                                //  Asesor(a)
                                preg_match('/asesor \(a\):\s*(.+)/i', $text, $asesorMatch);
                                $asesor = $asesorMatch[1] ?? null;
                                //  Modulo
                                preg_match('/módulo:?\s+([IVXLCDM]+)/iu', $text, $match);
                                $modulo_romano = $match[1] ?? null;
                                $modulo_entero = $modulo_romano ? $this->romano_entero($modulo_romano) : null;
                                //  Diplomado
                                preg_match('/Diplomado en Línea(?: de)?\s+“([^”]+)”/i', $text, $diplomadoMatch);
                                $diplomado = $diplomadoMatch[1] ?? null;
                                //  Extraer fechas de inicio y fin
                                preg_match('/a cabo del\s+(\d{1,2}\s+de\s+\w+)\s+al\s+(\d{1,2}\s+de\s+\w+)/iu', $text, $rango);
                                $inicio = $rango[1] ?? null;
                                $fin = $rango[2] ?? null;
                                // Mostrar resultados
                                Log::info("{$diplomado} {$modulo_entero} {$asesor} {$inicio} {$fin}");
                                $success_flag+=1;
                                //$message->setFlag('Seen');

                            }
                        }
                    }
                }
            }
            if ($success_flag) {
                return response()->json([
                    'success' => true,
                    'message' => "Se agregaron {$success_flag} registro(s)"
                ], 200);    
            }
            else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró un correo o archivo PDF que coincida.'
                ], 404);    
            }
            
        } 
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function connection_test(Request $request) {
        try {
            $client = Client::account();
            $client->connect();
            return response()->json([
                'success' => true, 
                'message' => 'Conexión exitosa'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 400);
        }
    }

    function normaliza_texto($text) {
        $text = Normalizer::normalize($text, Normalizer::FORM_D); // necesita "intl"
        $text = preg_replace('/\p{Mn}/u', '', $text); // elimina marcas de acento
        $text = strtolower($text);
        return trim($text);
    }

    public function delete_pdf(){
        $folder = storage_path('app/public');
        $deletedFiles = 0;

        foreach (glob($folder . '/*.pdf') as $file) {
            if (unlink($file)) {
                $deletedFiles++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Se eliminaron {$deletedFiles} archivo(s) PDF temporales."
        ]);
    }

    function romano_entero($romano) {
        $mapa = [
            'I' => 1,
            'II' => 2,
            'III' => 3,
            'IV' => 4,
            'V' => 5,
            'VI' => 6,
            'VII' => 7,
            'VIII' => 8
        ];
    
        $romano = strtoupper(trim($romano));
        return $mapa[$romano] ?? null;
    }
    
}
