<?php

/* #######################################
 * dir check
 */#######################################

if(!is_dir(PATH_LOCALE)){
  mkdir(PATH_LOCALE, 0755, true);
}

