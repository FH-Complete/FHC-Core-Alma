<?php

$config['export_path'] = ''; //XML Path für den Export
$config['export_view'] = 'exportProjectsFHTW'; //View Name für das Erstellen des XMLs
$config['projects_sync'] = array('Diplom'); //Welche Arbeiten sollen übertragen werden
$config['project_abgabe_datum'] = '2023-01-07'; //Ab welchen Abgabedatum soll gesynct werden >=
$config['project_url'] = '../FHC-Core-Alma/rest/v1/Export/pdf?id='; //Pfad für den API Call
$config['pdf_path'] = PAABGABE_PATH; //Pfad für die PDF-Dateien
$config['filename_prefix'] = '04_ftw'; //Filename Prefix für den XML-Export
$config['project_freigeschaltet_days'] = '60'; //Wie lange ist die PDF abrufbar über die API
$config['project_sync_delay_days'] = '14'; //Wie viele Tage nach dem Abgabedatum soll die Arbeit übertragen werden