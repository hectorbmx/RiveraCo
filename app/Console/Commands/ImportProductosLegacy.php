<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportProductosLegacy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:import-productos-legacy';
    protected $signature = 'productos:import {file : Ruta del archivo Excel}';


    /**
     * The console command description.
     *
     * @var string
     */
    // protected $description = 'Command description';
    protected $description = 'Importa productos legacy desde Excel (upsert por SKU)';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
