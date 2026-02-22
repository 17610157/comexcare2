@echo off
echo ========================================
echo EJECUCION DE OPTIMIZACIONES - LARAGON
echo ========================================
echo.

echo Verificando entorno...
cd /d %~dp0
echo Directorio actual: %CD%
echo.

echo ========================================
echo PRUEBA 1: CORRECCION DEL ERROR SQL
echo ========================================
echo.
php test_sql_fix.php
echo.
echo Presiona una tecla para continuar...
pause > nul

echo.
echo ========================================
echo PRUEBA 2: RENDIMIENTO COMPLETO
echo ========================================
echo.
php test_performance_laragon.php
echo.
echo Presiona una tecla para continuar...
pause > nul

echo.
echo ========================================
echo PRUEBA 3: VALIDACION 7k REGISTROS
echo ========================================
echo.
php test_7000_records.php
echo.
echo Presiona una tecla para continuar...
pause > nul

echo.
echo ========================================
echo EJECUCION INDIVIDUAL DE REPORTES
echo ========================================
echo.
echo Ejecutando reporte de vendedores...
php run_reports_test.php 1
echo.
echo Ejecutando reporte matricial...
php run_reports_test.php 2
echo.
echo Ejecutando reporte de metas...
php run_reports_test.php 3
echo.

echo ========================================
echo VERIFICACION COMPLETA
echo ========================================
echo.
echo Si todas las pruebas pasaron exitosamente:
echo ✓ El error SQL esta corregido
echo ✓ Los reportes funcionan correctamente
echo ✓ El rendimiento esta optimizado
echo.
echo Ahora puedes acceder a los reportes desde el navegador:
echo http://localhost/reportes/vendedores
echo http://localhost/reportes/vendedores-matricial
echo http://localhost/reportes/metas-ventas
echo.
echo ========================================
echo ¡OPTIMIZACIONES COMPLETADAS!
echo ========================================

pause