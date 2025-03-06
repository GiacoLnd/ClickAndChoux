<?php
namespace App\Service;

use DateTime;
use DateInterval;

class DeliveryTimeService
{
    // Fonction pour vérifier si c'est un jour férié
    public function isHoliday($date): bool
    {
        // Liste des jours fériés pour l'année
        $holidays = [
            '01-01',  // Jour de l'An
            '01-05',  // Fête du Travail
            '14-07',  // Fête Nationale
            '11-11', // Armistice - Anniversaire Pâtissière
            '25-12', // Noel
            '26-12', // Noel Alsace
            // Date jours fériés à ajouter si besoin - penser à les ajouter dans l'ordre
        ];

        // Extraire le jour et le mois de la date
        $dayMonth = (new DateTime($date))->format('d-m');

        return in_array($dayMonth, $holidays);
    }

    // Calcul de la date de livraison en fonction de l'heure de commande et du jour férié
    public function calculateDeliveryDate($orderTime, $isHoliday): DateTime
    {
        $currentDate = new DateTime();

        // Si l'heure de la commande est avant 16h, livraison le jour suivant
        // Si l'heure de la commande est après 16h, livraison le surlendemain
        $interval = new DateInterval('P1D');  // Ajoute un jour par défaut - P1D : notation utilisée par API DateInterval pour intervalle d'une journée 
        if (strtotime($orderTime) > strtotime('16:00')) {
            $interval = new DateInterval('P2D'); // Ajoute deux jours si la commande est après 16h - P2D : notation utilisée par API DateInterval pour intervalle de deux journées 
        }

        $deliveryDate = $currentDate->add($interval);

        // report au jour suivant si date tombe un jour férié
        while ($this->isHoliday($deliveryDate->format('Y-m-d'))) {
            $deliveryDate->add(new DateInterval('P1D')); // Report au jour suivant
        }

        return $deliveryDate;
    }
}
