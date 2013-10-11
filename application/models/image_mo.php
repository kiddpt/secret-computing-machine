<?php class Image_MO extends Generic_MO {

   var $widths;

   function __construct()
   {
      parent::__construct();
      $this->widths = array(200, 300, 400, 640, 800, 1200, 1920);
   }

   function multipleResize($full_path, $pic_resolution, $exclude = array()) {
      $result = array();
      $this->load->library('image_lib');

      $configs = array(
         );

      foreach($this->widths as $w) {
         if ($w <= $pic_resolution[0]) {
            $ratio = $pic_resolution[1]/$pic_resolution[0];
            $h = $w * $ratio;
            $configs[] = array("width" => $w, "height" => $h, "relpath" => "campaigns/$w", "path" => "/var/www/freebio/campaigns/$w", "maintain_ratio" => false);
         } else break;
      }

      // create Thumbnail cropped
      $target_thumb_size = 80;
      list($original_width, $original_height, $type, $attr) = getimagesize($full_path);
      if ($original_width > $original_height)
         $size = floor(($original_width)*($target_thumb_size/$original_height));
      else $size = floor(($original_height)*($target_thumb_size/$original_width));
      $thumb = "/var/www/freebio/campaigns/thumb/" . basename($full_path);

      $config = array(
         'image_library'     => 'gd2',
         'source_image'      => $full_path,
         'new_image'         => $thumb,
         'width'             => $size,
         'height'            => $size,
         'maintain_ratio'    => TRUE
      );
      $this->image_lib->initialize($config);
      $this->image_lib->resize();
      $this->image_lib->clear();
      list($current_width, $current_height, $type, $attr) = getimagesize($thumb);

      $config = array(
         'image_library'     => 'gd2',
         'maintain_ratio'    => false,
         'source_image'      => $thumb,
         'width'             => $target_thumb_size,
         'height'            => $target_thumb_size,
         'x_axis'            => ($current_width - $target_thumb_size) / 2,
         'y_axis'            => ($current_height - $target_thumb_size) / 2
      );

      $this->image_lib->initialize($config);
      $this->image_lib->crop();

      $result[] = array(
            "error"     => $this->image_lib->display_errors(),
            'new_image' => "campaigns/thumb",
            "full_path" => $full_path,
            'width'     => 80,
            'height'    => 80
            );

      foreach($configs as $conf) {
         $config = array(
            'source_image'      => $full_path,
            'new_image'         => $conf["path"],
            'width'             => $conf["width"],
            'height'            => $conf["height"],
            'maintain_ratio'    => $conf["maintain_ratio"]
         );
         $this->image_lib->initialize($config);
         $this->image_lib->resize();
         $this->image_lib->clear();

         $result[] = array(
            "error"     => $this->image_lib->display_errors(),
            'new_image' => $conf["relpath"],
            'width'     => $conf["width"],
            'height'    => $conf["height"]
            );
      }

      return $result;
   }
}