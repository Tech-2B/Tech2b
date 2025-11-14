<?php
/**
 * Prueba específica para Shared Drives (unidades compartidas)
 */

require_once '../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;

try {
    echo "<h2>Prueba de Shared Drives (Unidades Compartidas)</h2>";
    
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
    
    // Crear servicio de Drive
    $service = new Google\Service\Drive($client);
    echo "✅ Servicio de Drive creado<br>";
    
    // 1. Listar todas las unidades compartidas
    echo "<h3>1. Unidades Compartidas disponibles:</h3>";
    try {
        $drives = $service->drives->listDrives();
        if (count($drives->getDrives()) > 0) {
            foreach ($drives->getDrives() as $drive) {
                echo "- " . $drive->getName() . " (ID: " . $drive->getId() . ")<br>";
                if (strpos($drive->getName(), 'Oca') !== false || strpos($drive->getName(), 'construccion') !== false) {
                    echo "  ✅ Esta parece ser la unidad 'Oca construccion'<br>";
                }
            }
        } else {
            echo "No se encontraron unidades compartidas<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error listando unidades compartidas: " . $e->getMessage() . "<br>";
    }
    
    // 2. Probar acceso a la carpeta específica
    echo "<h3>2. Probando acceso a la carpeta principal:</h3>";
    $id_carpeta_principal = '1X6a3VpVkasNg-7dxtukxu7ZQjfGSOcDi';
    
    try {
        $carpeta = $service->files->get($id_carpeta_principal, [
            'supportsAllDrives' => true,
            'fields' => 'id,name,parents,driveId'
        ]);
        
        echo "✅ Carpeta encontrada:<br>";
        echo "- Nombre: " . $carpeta->getName() . "<br>";
        echo "- ID: " . $carpeta->getId() . "<br>";
        echo "- Drive ID: " . $carpeta->getDriveId() . "<br>";
        
        // 3. Probar crear una carpeta de prueba
        echo "<h3>3. Probando creación de carpeta:</h3>";
        $fileMetadata = new Google\Service\Drive\DriveFile([
            'name' => 'Prueba Sistema - ' . date('Y-m-d H:i:s'),
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$id_carpeta_principal]
        ]);
        
        $carpetaPrueba = $service->files->create($fileMetadata, [
            'fields' => 'id,name,parents',
            'supportsAllDrives' => true
        ]);
        
        echo "✅ Carpeta de prueba creada:<br>";
        echo "- ID: " . $carpetaPrueba->getId() . "<br>";
        echo "- Nombre: " . $carpetaPrueba->getName() . "<br>";
        
        // 4. Listar carpetas dentro de la carpeta principal
        echo "<h3>4. Carpetas en la carpeta principal:</h3>";
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
            echo "No hay carpetas en la carpeta principal<br>";
        }
        
        // 5. Eliminar carpeta de prueba
        echo "<h3>5. Limpiando carpeta de prueba:</h3>";
        $service->files->delete($carpetaPrueba->getId(), [
            'supportsAllDrives' => true
        ]);
        echo "✅ Carpeta de prueba eliminada<br>";
        
        echo "<br><h3>✅ RESULTADO: El sistema está configurado correctamente para trabajar con Shared Drives</h3>";
        
    } catch (Exception $e) {
        echo "❌ Error accediendo a la carpeta: " . $e->getMessage() . "<br>";
        echo "<br><strong>Posibles soluciones:</strong><br>";
        echo "1. Verificar que la cuenta de servicio tenga permisos de 'Editor' o 'Administrador' en la unidad compartida<br>";
        echo "2. Verificar que el ID de la carpeta sea correcto<br>";
        echo "3. Verificar que la unidad compartida esté activa<br>";
        echo "4. Verificar que el archivo de credenciales tenga los permisos correctos<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>
