<div class="my_meta_control">
 



	<div>Show in featured posts slider?
	<?php $mb->the_field('cb_single'); ?>
	<input type="checkbox" name="<?php $mb->the_name(); ?>" value="yes"<?php $mb->the_checkbox_state('yes'); ?>/>
        </div>
        <div>Suppress current slider image?
	<?php $mb->the_field('cb_single2'); ?>
	<input type="checkbox" name="<?php $mb->the_name(); ?>" value="yes"<?php $mb->the_checkbox_state('yes'); ?>/>
        </div>
		<div>Show in tabbed widget?
	<?php $mb->the_field('cb_single3'); ?>
	<input type="checkbox" name="<?php $mb->the_name(); ?>" value="yes"<?php $mb->the_checkbox_state('yes'); ?>/>
        </div>


</div>