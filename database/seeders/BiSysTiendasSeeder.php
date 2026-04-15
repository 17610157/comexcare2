<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BiSysTiendasSeeder extends Seeder
{
    public function run(): void
    {
        // NOTA: Esta tabla normalmente viene de la base de datos del sistema original
        // Los datos aquí son de ejemplo. Debes popularlos con los datos reales de tu sistema.

        $tiendas = [
            // PENINSULA
            ['clave_tienda' => 'PEN001', 'nombre' => 'Cancún', 'id_plaza' => 'PENINSULA', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'CUN', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'PEN002', 'nombre' => 'Playa del Carmen', 'id_plaza' => 'PENINSULA', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'PCM', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'PEN003', 'nombre' => 'Mérida Centro', 'id_plaza' => 'PENINSULA', 'zona' => 'ZONA SUR', 'clave_alterna' => 'MID', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'PEN004', 'nombre' => 'Mérida Norte', 'id_plaza' => 'PENINSULA', 'zona' => 'ZONA SUR', 'clave_alterna' => 'MIDN', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'PEN005', 'nombre' => 'Valladolid', 'id_plaza' => 'PENINSULA', 'zona' => 'ZONA SUR', 'clave_alterna' => 'VLL', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'PEN006', 'nombre' => 'Tulum', 'id_plaza' => 'PENINSULA', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'TLM', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'PEN007', 'nombre' => 'Cozumel', 'id_plaza' => 'PENINSULA', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'CZM', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'PEN008', 'nombre' => 'Chetumal', 'id_plaza' => 'PENINSULA', 'zona' => 'ZONA SUR', 'clave_alterna' => 'CTM', 'estado' => 'A', 'id_tipo' => 1],

            // CANCUN
            ['clave_tienda' => 'CAN001', 'nombre' => 'Cancún Centro', 'id_plaza' => 'CANCUN', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'CUNC', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'CAN002', 'nombre' => 'Cancún Zona Hotelera', 'id_plaza' => 'CANCUN', 'zona' => 'ZONA HOTELERA', 'clave_alterna' => 'CUNZH', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'CAN003', 'nombre' => 'Cancún Puerto Juárez', 'id_plaza' => 'CANCUN', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'CUNPJ', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'CAN004', 'nombre' => 'Isla Mujeres', 'id_plaza' => 'CANCUN', 'zona' => 'ZONA ISLA', 'clave_alterna' => 'ISM', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'CAN005', 'nombre' => 'Cancún La Luna', 'id_plaza' => 'CANCUN', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'CUNLL', 'estado' => 'A', 'id_tipo' => 1],

            // MERIDA
            ['clave_tienda' => 'MER001', 'nombre' => 'Mérida Calle 60', 'id_plaza' => 'MERIDA', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'MER60', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'MER002', 'nombre' => 'Mérida Francisco de Montejo', 'id_plaza' => 'MERIDA', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'MERFM', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'MER003', 'nombre' => 'Mérida García Ginerés', 'id_plaza' => 'MERIDA', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'MERGG', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'MER004', 'nombre' => 'Mérida Altabrisa', 'id_plaza' => 'MERIDA', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'MERALT', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'MER005', 'nombre' => 'Mérida Plaza La Isla', 'id_plaza' => 'MERIDA', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'MERPLI', 'estado' => 'A', 'id_tipo' => 1],

            // VILLA
            ['clave_tienda' => 'VIL001', 'nombre' => 'Villahermosa Centro', 'id_plaza' => 'VILLA', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'VHSC', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'VIL002', 'nombre' => 'Villahermosatabasco 2000', 'id_plaza' => 'VILLA', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'VHS2000', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'VIL003', 'nombre' => 'Villahermosa Guayabal', 'clave_alterna' => 'VHSGUAY', 'id_plaza' => 'VILLA', 'zona' => 'ZONA SUR', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'VIL004', 'nombre' => 'Villahermosa La Ceiba', 'id_plaza' => 'VILLA', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'VHSCEIBA', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'VIL005', 'nombre' => 'Villahermosa Pomoca', 'id_plaza' => 'VILLA', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'VHSPOM', 'estado' => 'A', 'id_tipo' => 1],

            // XALAPA
            ['clave_tienda' => 'XAL001', 'nombre' => 'Xalapa Centro', 'id_plaza' => 'XALAPA', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'XALC', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'XAL002', 'nombre' => 'Xalapa Flores', 'id_plaza' => 'XALAPA', 'zona' => 'ZONA SUR', 'clave_alterna' => 'XALF', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'XAL003', 'nombre' => 'Xalapa Plaza Crystal', 'id_plaza' => 'XALAPA', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'XALPC', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'XAL004', 'nombre' => 'Xalapa Rafael Murillo', 'id_plaza' => 'XALAPA', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'XALRM', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'XAL005', 'nombre' => 'Xalapa Universidad', 'id_plaza' => 'XALAPA', 'zona' => 'ZONA ESTE', 'clave_alterna' => 'XALUNIV', 'estado' => 'A', 'id_tipo' => 1],

            // NICARAGUA
            ['clave_tienda' => 'NIC001', 'nombre' => 'Managua Centro', 'id_plaza' => 'NICARAGUA', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'MGA1', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'NIC002', 'nombre' => 'Managua Galerías', 'id_plaza' => 'NICARAGUA', 'zona' => 'ZONA ESTE', 'clave_alterna' => 'MGAGAL', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'NIC003', 'nombre' => 'Managua Portón', 'id_plaza' => 'NICARAGUA', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'MGAPRT', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'NIC004', 'nombre' => 'Managua Rubenia', 'id_plaza' => 'NICARAGUA', 'zona' => 'ZONA OESTE', 'clave_alterna' => 'MGARUB', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'NIC005', 'nombre' => 'Granada', 'id_plaza' => 'NICARAGUA', 'zona' => 'ZONA SUR', 'clave_alterna' => 'GRA', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'NIC006', 'nombre' => 'León', 'id_plaza' => 'NICARAGUA', 'zona' => 'ZONA OESTE', 'clave_alterna' => 'LEON', 'estado' => 'A', 'id_tipo' => 1],

            // CHETUMAL
            ['clave_tienda' => 'CHE001', 'nombre' => 'Chetumal Centro', 'id_plaza' => 'CHETUMAL', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'CHEC', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'CHE002', 'nombre' => 'Chetumal Blvd', 'id_plaza' => 'CHETUMAL', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'CHEBLVD', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'CHE003', 'nombre' => 'Chetumal Payo Obispo', 'id_plaza' => 'CHETUMAL', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'CHEPO', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'CHE004', 'nombre' => 'Chetumal Zaragoza', 'id_plaza' => 'CHETUMAL', 'zona' => 'ZONA SUR', 'clave_alterna' => 'CHEZAR', 'estado' => 'A', 'id_tipo' => 1],

            // COLIMA
            ['clave_tienda' => 'COL001', 'nombre' => 'Colima Centro', 'id_plaza' => 'COLIMA', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'COLC', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'COL002', 'nombre' => 'Colima San José', 'id_plaza' => 'COLIMA', 'zona' => 'ZONA ESTE', 'clave_alterna' => 'COLSJ', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'COL003', 'nombre' => 'Manzanillo', 'id_plaza' => 'COLIMA', 'zona' => 'ZONA COSTA', 'clave_alterna' => 'MZT', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'COL004', 'nombre' => 'Tecomán', 'id_plaza' => 'COLIMA', 'zona' => 'ZONA SUR', 'clave_alterna' => 'TCM', 'estado' => 'A', 'id_tipo' => 1],

            // TAMPICO
            ['clave_tienda' => 'TAM001', 'nombre' => 'Tampico Centro', 'id_plaza' => 'TAMPICO', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'TAM1', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'TAM002', 'nombre' => 'Tampico Universidad', 'id_plaza' => 'TAMPICO', 'zona' => 'ZONA ESTE', 'clave_alterna' => 'TAMUNIV', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'TAM003', 'nombre' => 'Tampico Primavera', 'id_plaza' => 'TAMPICO', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'TAMPV', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'TAM004', 'nombre' => 'Ciudad Madero', 'id_plaza' => 'TAMPICO', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'CDM', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'TAM005', 'nombre' => 'Pánuco', 'id_plaza' => 'TAMPICO', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'PNC', 'estado' => 'A', 'id_tipo' => 1],

            // SOLIDARIO
            ['clave_tienda' => 'SOL001', 'nombre' => 'Solidaridad Playa del Carmen', 'id_plaza' => 'SOLIDARIO', 'zona' => 'ZONA CENTRO', 'clave_alterna' => 'SOLPCM', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'SOL002', 'nombre' => 'Solidaridad Tulum', 'id_plaza' => 'SOLIDARIO', 'zona' => 'ZONA NORTE', 'clave_alterna' => 'SOLTLM', 'estado' => 'A', 'id_tipo' => 1],
            ['clave_tienda' => 'SOL003', 'nombre' => 'Solidaridad Cozumel', 'id_plaza' => 'SOLIDARIO', 'zona' => 'ZONA ESTE', 'clave_alterna' => 'SOLCZM', 'estado' => 'A', 'id_tipo' => 1],

            // CEDIS (Centros de Distribución)
            ['clave_tienda' => 'CEDI001', 'nombre' => 'CEDI Península', 'id_plaza' => 'PENINSULA', 'zona' => 'CEDI', 'clave_alterna' => 'CEDIPEN', 'estado' => 'A', 'id_tipo' => 2],
            ['clave_tienda' => 'CEDI002', 'nombre' => 'CEDI Cancún', 'id_plaza' => 'CANCUN', 'zona' => 'CEDI', 'clave_alterna' => 'CEDICUN', 'estado' => 'A', 'id_tipo' => 2],
            ['clave_tienda' => 'CEDI003', 'nombre' => 'CEDI Mérida', 'id_plaza' => 'MERIDA', 'zona' => 'CEDI', 'clave_alterna' => 'CEDIMER', 'estado' => 'A', 'id_tipo' => 2],
            ['clave_tienda' => 'CEDI004', 'nombre' => 'CEDI Villahermosa', 'id_plaza' => 'VILLA', 'zona' => 'CEDI', 'clave_alterna' => 'CEDIVIL', 'estado' => 'A', 'id_tipo' => 2],
        ];

        foreach ($tiendas as $tienda) {
            DB::table('bi_sys_tiendas')->updateOrInsert(
                ['clave_tienda' => $tienda['clave_tienda']],
                $tienda
            );
        }

        $this->command->info('✓ Tiendas insertadas: '.count($tiendas));
        $this->command->warn('⚠️ NOTA: La tabla bi_sys_tiendas debe sincronizarse con la base de datos del sistema original para datos reales.');
    }
}
