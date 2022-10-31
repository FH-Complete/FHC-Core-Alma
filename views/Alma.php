<?php
	$this->load->view(
		'templates/FHC-Header',
		array(
			'title' => 'Alma',
			'jquery3' => true,
			'jqueryui1' => true,
			'bootstrap3' => true,
			'fontawesome4' => true,
			'sbadmintemplate3' => true,
			'ajaxlib' => true,
			'navigationwidget' => true
		)
	);
?>

<body>
	<div id="wrapper">

		<?php echo $this->widgetlib->widget('NavigationWidget'); ?>

		<div id="page-wrapper">
			<div class="container-fluid">
				<div class="row">
					<div class="col-lg-12">
						<h3 class="page-header">Alma <small> | Datenexport</small></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<p>Die Userdaten werden zwischen dem FH Campus und dem ALMA Bibliotheksystem synchronisiert.</p>
						<p>Die aktualisierten Daten werden als XML ZIP File exportiert, und müssen dann im ALMA System hochgeladen werden.</p>
					</div>
				</div>
				<br><br>
				<div class="row">
					<div class="col-xs-8">
						<h4>Aktuell vom Export ausgeschlossen</h4>
						<br>
						<div class="panel panel-default">
							<div class="panel-heading panel-title">
								Doppelte Personeneinträge
								<a class="pull-right" role="link" target="_self"
								   href=""><i class="fa fa-refresh" aria-hidden="true"></i>
								</a>
							</div>
							<div class="panel-body">
								<table class="table table-condensed">
									<thead>
									<th>Nachname</th>
									<th>Vorname</th>
									<th>1. Person ID <small>(in ALMA)</small></th>
									<th>2. Person ID</th>
									<th>Zusammenlegen</th>
									</thead>
									<?php foreach ($double_person_arr as $double_person): ?>
										<tr>
											<td class="col-md-3"><?php echo $double_person->nachname ?></td>
											<td class="col-md-2"><?php echo $double_person->vorname ?></td>
											<td class="col-md-2 text-center"><?php echo $double_person->alma_person_id ?></td>
											<td class="col-md-1 text-center"><?php echo $double_person->person_id ?></td>
											<td class="col-md-2 text-center"><a role="link" target="_blank"
																	   href="<?php echo base_url().'vilesci/stammdaten/personen_wartung.php?person_id_1='. $double_person->alma_person_id. '&person_id_2='. $double_person->person_id;?>">
													<i class="fa fa-external-link" aria-hidden="true"></i>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</table>
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>
