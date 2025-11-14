<?php
session_start();
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';
require_once '../../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

// Simular sesión de administrador
$_SESSION['rol'] = 'Administrador';
$_SESSION['id_usuario'] = 1; 

try {
    $funciones = new FuncionesGenerales();
    
    // Verificar si el archivo de credenciales existe
    $credentials_path = __DIR__ . '/../drive/credenciales/ocaconstruccion-b8ddbf846879.json';
    if (!file_exists($credentials_path)) {
        throw new Exception("Archivo de credenciales no encontrado en: " . $credentials_path);
    }
    
    // Intentar inicializar Google Client
    $client = new Client();
    $client->setAuthConfig($credentials_path);
    $client->addScope(Drive::DRIVE);
    $client->setAccessType('offline');
    
    // Intentar crear el servicio de Drive
    $service = new Drive($client);
    
    // Probar acceso a la carpeta específica con soporte para Shared Drives
    $id_carpeta_test = '1sQYn2gKfa_PgBVkIg46NCHok-omVPB3a';
    error_log("Probando acceso a carpeta con Shared Drives: " . $id_carpeta_test);
    
    try {
        $carpeta = $service->files->get($id_carpeta_test, [
            'fields' => 'id,name,mimeType,parents',
            'supportsAllDrives' => true
        ]);
        error_log("Carpeta encontrada: " . $carpeta->getName() . " (ID: " . $carpeta->getId() . ", MIME: " . $carpeta->getMimeType() . ")");
        
        // Verificar que es una carpeta
        if ($carpeta->getMimeType() !== 'application/vnd.google-apps.folder') {
            error_log("ADVERTENCIA: El ID no corresponde a una carpeta, es: " . $carpeta->getMimeType());
        }
        
        $funciones->fnRegresarRespuestaJsonEncode(200, true, 'success', '¡Éxito!', 'Carpeta encontrada y accesible: ' . $carpeta->getName());
        
    } catch (Exception $e) {
        error_log("Error accediendo a la carpeta con Shared Drives: " . $e->getMessage());
        
        // Intentar sin Shared Drives como fallback
        try {
            error_log("Intentando sin Shared Drives...");
            $carpeta = $service->files->get($id_carpeta_test, ['fields' => 'id,name,mimeType']);
            error_log("Carpeta encontrada sin Shared Drives: " . $carpeta->getName());
            $funciones->fnRegresarRespuestaJsonEncode(200, true, 'success', '¡Éxito!', 'Carpeta encontrada (sin Shared Drives): ' . $carpeta->getName());
        } catch (Exception $e2) {
            error_log("Error también sin Shared Drives: " . $e2->getMessage());
            $funciones->fnRegresarRespuestaJsonEncode(500, false, 'error', 'Error de carpeta', 'Error accediendo a la carpeta: ' . $e->getMessage());
        }
    }

} catch (Exception $e) {
    error_log("Error en test_drive_simple.php: " . $e->getMessage());
    $funciones->fnRegresarRespuestaJsonEncode(500, false, 'error', 'Error de prueba', 'Error inicializando Google Drive API: ' . $e->getMessage());
}
?>
