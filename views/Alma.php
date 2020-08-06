<?php
	$this->load->view(
		'templates/FHC-Header',
		array(
			'title' => 'Alma',
			'jquery' => true,
			'jqueryui' => true,
			'bootstrap' => true,
			'fontawesome' => true,
			'sbadmintemplate' => true,
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
                        <p>Die aktualisierten Daten werden als XML file exportiert, gezippt und müssen dann im ALMA System hochgeladen werden.</p>
                    </div>
                </div>
                <br><br>
                <div class="row">
                    <div class="col-lg-12">
                        <a role="button" class="btn btn-default" href="<?php echo site_url().'/extensions/FHC-Core-Alma/Alma/export';?>">Export XML</a>
                    </div>
                </div>
			</div>
		</div>
	</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>
