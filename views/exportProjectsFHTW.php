<?php

$project_url = $this->config->item('project_url') ?: show_error('Missing config entry for projekt_url');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<collection>
<?php foreach ($projectsAllData as $project): ?>
	<record>
		<leader>00000nam a2200421 c 4500</leader>
		<controlfield tag="007">cr |||||||||||</controlfield>
		<controlfield tag="008"><?php echo date('ymd') . "s" . date('Y', strtotime($project->abgabedatum)) . '    ||||||||om||||||||||' . (($project->sprache_arbeit === 'German') ? 'ger' : 'eng') . '|c'; ?></controlfield>
		<datafield tag="040" ind1=" " ind2=" ">
			<subfield code="a">AT-FTW</subfield>
			<subfield code="b">ger</subfield>
			<subfield code="d">AT-FTW</subfield>
			<subfield code="e">rda</subfield>
		</datafield>
		<datafield tag="041" ind1=" " ind2=" ">
			<subfield code="a"><?php echo ($project->sprache_arbeit === 'German') ? 'ger' : 'eng'; ?></subfield>
		</datafield>
		<datafield tag="044" ind1=" " ind2=" ">
			<subfield code="c">XA-AT</subfield>
		</datafield>
		<datafield tag="100" ind1="1" ind2=" ">
			<subfield code="a"><?php echo $project->author[0]->nachname . ', ' . $project->author[0]->vorname ?></subfield>
			<subfield code="4">aut</subfield>
		</datafield>
		<datafield tag="245" ind1="1" ind2="0">
			<subfield code="a"><?php echo $project->titel; ?></subfield>
			<subfield code="c"><?php echo (($project->sprache_arbeit === 'German') ? ('ausgeführt von: ' . $project->author[0]->vorname . ' ' . $project->author[0]->nachname) : ('by: ' .  $project->author[0]->vorname . ' ' . $project->author[0]->nachname))  ?></subfield>
		</datafield>
		<datafield tag="264" ind1="1" ind2=" ">
			<subfield code="a">Wien</subfield>
			<subfield code="c"><?php echo date('Y', strtotime($project->abgabedatum));?></subfield>
		</datafield>
		<datafield tag="300" ind1=" " ind2=" ">
			<subfield code="a">1 Online-Ressource (<?php echo $project->seitenanzahl ?> Seiten)</subfield>
		</datafield>
		<datafield tag="502" ind1=" " ind2=" ">
			<subfield code="b">Masterarbeit</subfield>
			<subfield code="c">Fachhochschule Technikum Wien</subfield>
			<subfield code="d"><?php echo date('Y', strtotime($project->abgabedatum)); ?></subfield>
		</datafield>
		<datafield tag="506" ind1="0" ind2=" ">
			<subfield code="f">Unrestricted online access</subfield>
			<subfield code="2">star</subfield>
		</datafield>
		<datafield tag="520" ind1=" " ind2=" ">
			<subfield code="a"><?php echo "ger: " . str_replace(array("\r\n", "\r", "\n"), '', $project->abstract); ?></subfield>
		</datafield>
		<datafield tag="520" ind1=" " ind2=" ">
			<subfield code="a"><?php echo "eng: " . str_replace(array("\r\n", "\r", "\n"), '', $project->abstract_en); ?></subfield>
		</datafield>
		<datafield tag="856" ind1="4" ind2="0">
			<subfield code="u"><?php echo $project_url . $project->pseudo_id ?></subfield>
			<subfield code="x">FTW</subfield>
			<subfield code="3">Volltext</subfield>
		</datafield>
		<datafield tag="970" ind1="2" ind2=" ">
			<subfield code="d">HS-MASTER</subfield>
		</datafield>
		<?php foreach ($project->erstBegutachter as $erstBegutachter): ?>
			<datafield tag="971" ind1="1" ind2=" ">
				<subfield code="a"><?php echo $erstBegutachter->nachname . ', ' . $erstBegutachter->vorname; ?></subfield>
			</datafield>
		<?php endforeach; ?>
		<?php foreach ($project->zweitBegutachter as $zweitBegutachter): ?>
			<datafield tag="971" ind1="1" ind2=" ">
				<subfield code="a"><?php echo $zweitBegutachter->nachname . ', ' . $zweitBegutachter->vorname; ?></subfield>
			</datafield>
		<?php endforeach; ?>
		<datafield tag="971" ind1="3" ind2=" ">
			<subfield code="a"><?php echo date('Y', strtotime($project->abgabedatum)); ?></subfield>
		</datafield>
		<datafield tag="971" ind1="5" ind2=" ">
			<subfield code="0">ioo:FTW:<?php echo $project->melde_stg_kz; ?></subfield>
		</datafield>
		<?php if (!is_null($project->gesperrtbis)): ?>
			<datafield tag="971" ind1="7" ind2=" ">
				<subfield code="a">Arbeit gesperrt</subfield>
				<subfield code="b"><?php echo date('Y-m', strtotime($project->abgabedatum)); ?></subfield>
				<subfield code="c"><?php echo date('Y-m', strtotime($project->gesperrtbis)); ?></subfield>
				<subfield code="d">Gesperrt</subfield>
			</datafield>
		<?php endif; ?>
		<datafield tag="971" ind1="8" ind2=" ">
			<subfield code="a"><?php echo str_replace(array(";", ","), ' /', $project->schlagwoerter); ?></subfield>
		</datafield>
		<datafield tag="971" ind1="9" ind2=" ">
			<subfield code="a"><?php echo str_replace(array(";", ","), ' /', $project->schlagwoerter_en); ?></subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="035">
			<subfield code="a">(VLID)AT-FTW</subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="336">
			<subfield code="b">txt</subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="337">
			<subfield code="b">c</subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="338">
			<subfield code="b">cr</subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="347">
			<subfield code="a">Textdatei</subfield>
			<subfield code="b">PDF</subfield>
		</datafield>
		<datafield ind1=" " ind2="7" tag="655">
			<subfield code="a">Hochschulschrift</subfield>
			<subfield code="0">(DE-588)4113937-9</subfield>
			<subfield code="2">gnd-content</subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="990">
			<subfield code="z">Daten sind nicht geprüft</subfield>
			<subfield code="9">LOCAL</subfield>
		</datafield>
	</record>
<?php endforeach; ?>
</collection>
