<?php

namespace AppBundle\Service\Twig;

class TwigDate extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('inscriptionDate', array($this, 'inscriptionDate')),
            //on peux ajouter d'autre méthode en instanciant d'autres \Twig_SimpleFilter
        );
    }

    public function inscriptionDate($date)
    {
        if ($date instanceof \DateTime) {
          $today = new \DateTime();
          $now = $today->format('Y/m/d');
          if ($now == $date->format('Y/m/d')) {
              $date = "Aujourd’hui à ".$date->format('H').'h'.$date->format('i');
              return $date;
          }
          else{
              $date = $date->format('d').' '.lcfirst($this->month($date->format('m'))).' '.$date->format('Y')." à ".$date->format('H').'h'.$date->format('i');
              return $date;
          }
        }
        return false;
   }


   protected function month($month){
      switch ($month) {
        case '01':
          return 'Janvier';
          break;
        case '02':
          return 'Fevrier';
          break;
        case '03':
          return 'Mars';
          break;
        case '04':
          return 'Avril';
          break;
        case '05':
          return 'Mai';
          break;
        case '06':
          return 'Juin';
          break;
        case '07':
          return 'Juillet';
          break;
        case '08':
          return 'Août';
          break;
        case '09':
          return 'Septembre';
          break;
        case '10':
          return 'Octobre';
          break;
        case '11':
          return 'Novembre';
          break;
        case '12':
          return 'Décembre';
          break;
        
        default:
          return '';
          break;
      }
   }
}