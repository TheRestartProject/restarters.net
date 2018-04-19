<div class="container-fluid">
	<section class="row" id="impact-dataviz">
		<div class="col-md-12">
			<div class="di_consume <?php echo $info['consume_class']; ?>">
				<img src="/assets/icons/<?php echo $info['consume_image']; ?>" class="img-responsive">
				<div class="text">
					<div class="blue"><?php echo $info['consume_label']; ?></div>
					<div class="consume"><?php echo $info['consume_eql_to']; ?></div>
				</div>
			</div>

			<div class="di_manufacture">
				<div class="col-md-12 text-center"><div class="lightblue"><?php echo $info['manufacture_label']; ?></div></div>
				<?php for($i = 1; $i<= $info['manufacture_eql_to']; $i++){ ?>
					<div class="col-md-3 col-sm-3 col-xs-4 text-center">
						<img src="/assets/icons/<?php echo $info['manufacture_img']; ?>" class="img-responsive">
					</div>
				<?php } ?>
				<div class="col-md-12 col-sm-12 col-xs-12 text-center">
					<div class="legend">1 <img src="/assets/icons/<?php echo $info['manufacture_img']; ?>"> = <?php echo $info['manufacture_legend']; ?> <?php _t("(approximately)");?></div>

				</div>
			</div>

		</div>
	</section>
</div>
