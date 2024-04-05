<?php

$project_url = $this->config->item('project_url') ?: show_error('Missing config entry for projekt_url');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<collection>
<?php foreach ($projectsAllData as $project): ?>
	<record>
		<leader><![CDATA[00000nam a2200421 c 4500]]></leader>
		<controlfield tag="007"><![CDATA[cr |||||||||||]]></controlfield>
		<controlfield tag="008"><![CDATA[<?php echo date('ymd') . "s" . date('Y', strtotime($project->abgabedatum)) . '    ||||||||om||||||||||' . (($project->sprache_arbeit === 'German') ? 'ger' : 'eng') . '|c'; ?>]]></controlfield>
		<datafield tag="040" ind1=" " ind2=" ">
			<subfield code="a"><![CDATA[AT-FTW]]></subfield>
			<subfield code="b"><![CDATA[ger]]></subfield>
			<subfield code="d"><![CDATA[AT-FTW]]></subfield>
			<subfield code="e"><![CDATA[rda]]></subfield>
		</datafield>
		<datafield tag="041" ind1=" " ind2=" ">
			<subfield code="a"><![CDATA[<?php echo (($project->sprache_arbeit === 'German') ? 'ger' : 'eng'); ?>]]></subfield>
		</datafield>
		<datafield tag="044" ind1=" " ind2=" ">
			<subfield code="c"><![CDATA[XA-AT]]></subfield>
		</datafield>
		<datafield tag="100" ind1="1" ind2=" ">
			<subfield code="a"><![CDATA[<?php echo $project->author[0]->nachname . ', ' . $project->author[0]->vorname ?>]]></subfield>
			<subfield code="4"><![CDATA[aut]]></subfield>
		</datafield>
		<datafield tag="245" ind1="1" ind2="0">
			<subfield code="a"><![CDATA[<?php echo $project->titel; ?>]]></subfield>
			<subfield code="c"><![CDATA[<?php echo (($project->sprache_arbeit === 'German') ? ('ausgeführt von: ' . $project->author[0]->vorname . ' ' . $project->author[0]->nachname) : ('by: ' .  $project->author[0]->vorname . ' ' . $project->author[0]->nachname))?>]]></subfield>
		</datafield>
		<datafield tag="264" ind1="1" ind2=" ">
			<subfield code="a"><![CDATA[Wien]]></subfield>
			<subfield code="c"><![CDATA[<?php echo date('Y', strtotime($project->abgabedatum));?>]]></subfield>
		</datafield>
		<datafield tag="300" ind1=" " ind2=" ">
			<subfield code="a"><![CDATA[1 Online-Ressource (<?php echo $project->seitenanzahl ?> Seiten)]]></subfield>
		</datafield>
		<datafield tag="502" ind1=" " ind2=" ">
			<subfield code="b"><![CDATA[Masterarbeit]]></subfield>
			<subfield code="c"><![CDATA[Fachhochschule Technikum Wien]]></subfield>
			<subfield code="d"><![CDATA[<?php echo date('Y', strtotime($project->abgabedatum)); ?>]]></subfield>
		</datafield>
		<datafield tag="506" ind1="0" ind2=" ">
			<subfield code="f"><![CDATA[Unrestricted online access]]></subfield>
			<subfield code="2"><![CDATA[star]]></subfield>
		</datafield>
		<datafield tag="520" ind1=" " ind2=" ">
			<subfield code="a"><![CDATA[<?php echo "ger: " . str_replace(array("\r\n", "\r", "\n"), '', htmlspecialchars($project->abstract, ENT_DISALLOWED)); ?>]]></subfield>
		</datafield>
		<datafield tag="520" ind1=" " ind2=" ">
			<subfield code="a"><![CDATA[<?php echo "eng: " . str_replace(array("\r\n", "\r", "\n"), '', htmlspecialchars($project->abstract_en, ENT_DISALLOWED)); ?>]]></subfield>
		</datafield>
		<datafield tag="856" ind1="4" ind2="0">
			<subfield code="u"><![CDATA[<?php echo $project_url . $project->pseudo_id ?>]]></subfield>
			<subfield code="x"><![CDATA[FTW]]></subfield>
			<subfield code="3"><![CDATA[Volltext]]></subfield>
		</datafield>
		<datafield tag="970" ind1="2" ind2=" ">
			<subfield code="d"><![CDATA[HS-MASTER]]></subfield>
		</datafield>
		<?php foreach ($project->erstBegutachter as $erstBegutachter): ?>
			<datafield tag="971" ind1="1" ind2=" ">
				<subfield code="a"><![CDATA[<?php echo $erstBegutachter->nachname . ', ' . $erstBegutachter->vorname; ?>]]></subfield>
			</datafield>
		<?php endforeach; ?>
		<?php foreach ($project->zweitBegutachter as $zweitBegutachter): ?>
			<datafield tag="971" ind1="1" ind2=" ">
				<subfield code="a"><![CDATA[<?php echo $zweitBegutachter->nachname . ', ' . $zweitBegutachter->vorname; ?>]]></subfield>
			</datafield>
		<?php endforeach; ?>
		<datafield tag="971" ind1="3" ind2=" ">
			<subfield code="a"><![CDATA[<?php echo date('Y', strtotime($project->abgabedatum)); ?>]]></subfield>
		</datafield>
		<datafield tag="971" ind1="5" ind2=" ">
			<subfield code="0"><![CDATA[ioo:FTW:<?php echo $project->melde_stg_kz; ?>]]></subfield>
		</datafield>
		<?php if (!is_null($project->gesperrtbis)): ?>
			<datafield tag="971" ind1="7" ind2=" ">
				<subfield code="a"><![CDATA[Arbeit gesperrt]]></subfield>
				<subfield code="b"><![CDATA[<?php echo date('Y-m', strtotime($project->abgabedatum)); ?>]]></subfield>
				<subfield code="c"><![CDATA[<?php echo date('Y-m', strtotime($project->gesperrtbis)); ?>]]></subfield>
				<subfield code="d"><![CDATA[Gesperrt]]></subfield>
			</datafield>
		<?php endif; ?>
		<datafield tag="971" ind1="8" ind2=" ">
			<subfield code="a"><![CDATA[<?php echo (str_replace(array(";", ","), ' /', $project->schlagwoerter)); ?>]]></subfield>
		</datafield>
		<datafield tag="971" ind1="9" ind2=" ">
			<subfield code="a"><![CDATA[<?php echo (str_replace(array(";", ","), ' /', $project->schlagwoerter_en)); ?>]]></subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="035">
			<subfield code="a"><![CDATA[(VLID)AT-FTW]]></subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="336">
			<subfield code="b"><![CDATA[txt]]></subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="337">
			<subfield code="b"><![CDATA[c]]></subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="338">
			<subfield code="b"><![CDATA[cr]]></subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="347">
			<subfield code="a"><![CDATA[Textdatei]]></subfield>
			<subfield code="b"><![CDATA[PDF]]></subfield>
		</datafield>
		<datafield ind1=" " ind2="7" tag="655">
			<subfield code="a"><![CDATA[Hochschulschrift]]></subfield>
			<subfield code="0"><![CDATA[(DE-588)4113937-9]]></subfield>
			<subfield code="2"><![CDATA[gnd-content]]></subfield>
		</datafield>
		<datafield ind1=" " ind2=" " tag="990">
			<subfield code="z"><![CDATA[Daten sind nicht geprüft]]></subfield>
			<subfield code="9"><![CDATA[LOCAL]]></subfield>
		</datafield>
	</record>
<?php endforeach; ?>
</collection>
