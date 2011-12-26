<?php
ob_start('ob_gzhandler');
echo $sf_data->getRaw('text');
ob_end_flush();