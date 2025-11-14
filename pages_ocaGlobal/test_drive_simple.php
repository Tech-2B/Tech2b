<?php
/**
 * Prueba simple de Google Drive API
 */

// Incluir Google Drive API
require_once '../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;

try {
    echo "<h2>Prueba Simple de Google Drive API</h2>";
    
    // Ruta al archivo de credenciales
    $credentialsPath = __DIR__ . '/drive/credenciales/ocaconstruccion-b8ddbf846879.json';
    
    if (!file_exists($credentialsPath)) {
        echo "❌ Archivo de credenciales no encontrado: $credentialsPath<br>";
        exit;
    }
    
    echo "✅ Archivo de credenciales encontrado<br>";
    
    // Crear cliente de Google
    $client = new Google\Client();
    $client->setAuthConfig($credentialsPath);
    $client->addScope(Google\Service\Drive::DRIVE);
    $client->setAccessType('offline');
    
    echo "✅ Cliente de Google creado<br>";
    
    // Crear servicio de Drive
    $service = new Google\Service\Drive($client);
    echo "✅ Servicio de Drive creado<br>";
    
    // Probar acceso a la carpeta principal en Shared Drive
    $id_carpeta_principal = '1X6a3VpVkasNg-7dxtukxu7ZQjfGSOcDi';
    echo "<br>Probando acceso a carpeta ID: $id_carpeta_principal<br>";
    echo "Esta carpeta está en Shared Drive (unidad compartida)<br>";
    
    try {
        $carpeta = $service->files->get($id_carpeta_principal, [
            'supportsAllDrives' => true
        ]);
        echo "✅ Carpeta encontrada: " . $carpeta->getName() . "<br>";
        echo "✅ La carpeta principal existe y es accesible<br>";
        
        // Probar crear una carpeta de prueba
        echo "<br>Probando crear carpeta de prueba...<br>";
        $fileMetadata = new Google\Service\Drive\DriveFile([
            'name' => 'Prueba - ' . date('Y-m-d H:i:s'),
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$id_carpeta_principal]
        ]);
        
        $carpetaPrueba = $service->files->create($fileMetadata, [
            'fields' => 'id,name',
            'supportsAllDrives' => true
        ]);
        
        echo "✅ Carpeta de prueba creada exitosamente:<br>";
        echo "- ID: " . $carpetaPrueba->getId() . "<br>";
        echo "- Nombre: " . $carpetaPrueba->getName() . "<br>";
        
        // Eliminar carpeta de prueba
        $service->files->delete($carpetaPrueba->getId(), [
            'supportsAllDrives' => true
        ]);
        echo "✅ Carpeta de prueba eliminada<br>";
        
    } catch (Exception $e) {
        echo "❌ Error accediendo a la carpeta: " . $e->getMessage() . "<br>";
        echo "Verifica que:<br>";
        echo "1. El ID de la carpeta sea correcto<br>";
        echo "2. La cuenta de servicio tenga permisos en la unidad compartida<br>";
        echo "3. La unidad compartida esté activa<br>";
    }
    
    // Listar carpetas en la carpeta principal
    echo "<br><h3>Carpetas en la carpeta principal:</h3>";
    $query = "mimeType='application/vnd.google-apps.folder' and '$id_carpeta_principal' in parents and trashed=false";
    $results = $service->files->listFiles([
        'q' => $query,
        'fields' => 'files(id,name,mimeType)',
        'supportsAllDrives' => true
    ]);
    
    if (count($results->getFiles()) > 0) {
        foreach ($results->getFiles() as $file) {
            echo "- " . $file->getName() . " (ID: " . $file->getId() . ")<br>";
        }
    } else {
        echo "No se encontraron carpetas en la raíz.<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>
