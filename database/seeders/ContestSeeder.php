<?php

namespace Database\Seeders;

use App\Models\Contest;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ContestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Contest attivo
        Contest::create([
            'title' => 'Contest Fotografico Autunnale 2025',
            'description' => 'Cattura la bellezza dell\'autunno con le tue foto! Un contest dedicato ai colori caldi e alle atmosfere suggestive di questa stagione magica. Partecipa e vinci fantastici premi.',
            'status' => 'active',
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(15),
            'max_participants' => 100,
            'current_participants' => 47,
            'prize' => '€500 + Kit Fotografico Canon',
            'category' => 'natura',
            'entry_fee' => 10.00,
        ]);

        // Contest in arrivo
        Contest::create([
            'title' => 'Street Photography Challenge',
            'description' => 'Esplora le strade della tua città e immortala la vita urbana. Un contest dedicato alla fotografia di strada, dove ogni scatto racconta una storia unica.',
            'status' => 'upcoming',
            'start_date' => Carbon::now()->addDays(3),
            'end_date' => Carbon::now()->addDays(25),
            'max_participants' => 75,
            'current_participants' => 0,
            'prize' => '€300 + Workshop con Fotografo Professionista',
            'category' => 'street',
            'entry_fee' => 15.00,
        ]);

        // Contest terminato
        Contest::create([
            'title' => 'Ritratti d\'Emozione',
            'description' => 'Un contest dedicato all\'arte del ritratto. Cattura l\'essenza umana attraverso espressioni, sguardi e momenti di pura emozione.',
            'status' => 'ended',
            'start_date' => Carbon::now()->subDays(45),
            'end_date' => Carbon::now()->subDays(10),
            'max_participants' => 60,
            'current_participants' => 58,
            'prize' => '€400 + Sessione Fotografica con Modelle',
            'category' => 'ritratti',
            'entry_fee' => 12.00,
        ]);

        // Contest macro attivo
        Contest::create([
            'title' => 'Macro World - Il Mondo in Miniatura',
            'description' => 'Scopri il fascino del mondo macro! Insetti, fiori, dettagli nascosti: tutto ciò che l\'occhio non riesce a vedere nella vita quotidiana.',
            'status' => 'active',
            'start_date' => Carbon::now()->subDays(2),
            'end_date' => Carbon::now()->addDays(20),
            'max_participants' => 50,
            'current_participants' => 23,
            'prize' => '€350 + Obiettivo Macro Professionale',
            'category' => 'macro',
            'entry_fee' => 8.00,
        ]);

        // Contest paesaggi in arrivo
        Contest::create([
            'title' => 'Paesaggi Italiani - Bellezza Naturale',
            'description' => 'Celebra la bellezza del nostro paese attraverso paesaggi mozzafiato. Dalle Alpi alle coste mediterranee, mostra l\'Italia che ami.',
            'status' => 'upcoming',
            'start_date' => Carbon::now()->addDays(7),
            'end_date' => Carbon::now()->addDays(35),
            'max_participants' => 120,
            'current_participants' => 0,
            'prize' => '€600 + Viaggio Fotografico in Toscana',
            'category' => 'paesaggi',
            'entry_fee' => 20.00,
        ]);

        // Contest notturno attivo
        Contest::create([
            'title' => 'Notturno Urbano - Luci della Città',
            'description' => 'Esplora la fotografia notturna urbana. Giochi di luce, riflessi, atmosfere suggestive: cattura la magia della città che non dorme mai.',
            'status' => 'active',
            'start_date' => Carbon::now()->subDays(7),
            'end_date' => Carbon::now()->addDays(8),
            'max_participants' => 80,
            'current_participants' => 67,
            'prize' => '€450 + Treppiede Professionale',
            'category' => 'urbano',
            'entry_fee' => 18.00,
        ]);

        // Contest terminato recente
        Contest::create([
            'title' => 'Bianco e Nero Classico',
            'description' => 'Un omaggio alla fotografia in bianco e nero. Contrasti, texture, emozioni pure: dimostra che non servono i colori per creare arte.',
            'status' => 'ended',
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(3),
            'max_participants' => 90,
            'current_participants' => 89,
            'prize' => '€500 + Stampe Fine Art delle Foto Vincitrici',
            'category' => 'bianco_nero',
            'entry_fee' => 14.00,
        ]);
    }
}
