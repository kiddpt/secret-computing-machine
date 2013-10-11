<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Manager extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$this->load->view('welcome_message');
	}

	function removeimage() {
		if ($this->input->get("photo")) {
			if ($this->session->userdata($this->input->get("photo"))) {
				if ($this->input->get("multiple")) {
					$sizes = array("200", "300", "400", "640", "800", "1200", "1920", "thumb");
					$filepath = "./temp/" . $this->input->get("photo");
					if (file_exists($filepath))
						unlink($filepath);
					foreach($sizes as $s) {
						$filepath = "./temp/campaigns/$s/" . $this->input->get("photo");
						if (file_exists($filepath))
							unlink($filepath);
					}
				} else if (file_exists(".".$this->input->get("photo")))
						unlink(".".$this->input->get("photo"));
			}
			else {
				echo "NO";
			}
		}
		else {
			echo "NO parameters";
		}
	}

	function uploadLogo()
	{
		error_reporting(E_ERROR);
		$IMAGE_EXTENSIONS = array('jpg','jpeg','png','gif');

		$this->load->library('encrypt');
		$this->load->helper('generic_helper');

		$pic = $_FILES['pic'];

		$extension = get_extension($pic['name']);

		$hash = $this->encrypt->sha1($pic['name'].time());
		$filename = $hash.'.'.$extension;
		$path = TEMP_UPLOAD_FOLDER.$filename;

		if ($pic['name'] == "") {
			$data['size'] = $pic['size'];
			$data['status'] = false;
			$data['message'] = 'You did not select an image.';
		}
		else if(!in_array(get_extension($pic['name']),$IMAGE_EXTENSIONS))
		{
			$data["status"] = false;
			$data['message'] = 'Only '.implode(',',$IMAGE_EXTENSIONS).' files are allowed!';
		}
		else if(move_uploaded_file($pic['tmp_name'], $path)){
			$pic_res = getimagesize($path);
			$width = 0; $height = 1; // index of size values
			$ratio = $pic_res[$width]/$pic_res[$height];
			$data["ratio"] = $ratio;

			// Check image size if valid
			if (!($pic_res[$width] >= 800 && ($ratio <= 2 && $ratio >= 0.5))) {
				$data["status"] = false;
				$data['message'] = 'The allowed minimum resolution of photo is 800px by 600px.';
				unlink($path);
			}
			else if (!($pic_res[$width] >= 800 && ($ratio <= 2 && $ratio >= 0.5))) {
				$data["status"] = false;
				$data['message'] = 'The allowed minimum resolution of photo is 800px by 600px.';
				unlink($path);
			}
			else {
				$this->load->model("image_mo");
				$data["resize"] = $this->image_mo->multipleResize($path, $pic_res);
				$data["status"] = true;
				$data['message'] = 'File was uploaded successfuly!';
				$data['filename'] = $filename;
				$this->session->set_userdata($filename, true);
			}
		}
		else {
			$data['size'] = $pic['size'];
			$data['status'] = false;
			$data['message'] = 'File size exceeds the limit (2MB).';
		}

		//$data['filename'] = $path;
		//$data['ext'] = $IMAGE_EXTENSIONS;

		echo json_encode($data);
	}

	function upload_brandlogo() {
		$config['upload_path'] = '../freebio/brandlogo/';
		$config['allowed_types'] = 'gif|jpg|png';
		$config['max_size']	= '2048';
		$config['max_width']  = '800';
		$config['max_height']  = '800';
		$config['encrypt_name']	= TRUE;
		$brand_id = $this->input->post("brand_id");
		$brand_name = $this->input->post("brand_name");
		$brand_logo = $this->input->post("brand_logo");
		$token = $this->session->userdata("node_token");
		$uploaded = false;

		// Uploading
		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload("file"))
		{
			$error = $this->upload->display_errors();
			if ($brand_id && trim($error) == "<p>You did not select a file to upload.</p>")
			{
				$this->load->model("generic_mo");
				$node = $this->generic_mo->curl_with_error("http://192.241.222.166:8998/update_merchant_brand?account_id=" . urlencode($this->account_id) . "&brand_id=" . urlencode($brand_id) . "&brand_name=" . urlencode($brand_name) . "&api_token=" . urlencode($token));	
			}
			else 
			{
				echo json_encode(array(
					"status" => "error",
					"message" => $error,
					"statusCode" => 400
					));
				$this->output->set_status_header('400');
				return;
			}
		}
		else
		{
			// Resizing
			$data = $this->upload->data();
			$uploaded = true;
			$brand_logo = "../freebio/brandlogo/" . $data["file_name"];

			// create Thumbnail cropped
			$target_thumb_size = 100;
			list($original_width, $original_height, $type, $attr) = getimagesize($brand_logo);

			if ($original_width > $original_height)
			 $size = floor(($original_width)*($target_thumb_size/$original_height));
			else $size = floor(($original_height)*($target_thumb_size/$original_width));

			$config = array(
			 'source_image'      => $data["full_path"],
			 'width'             => $size,
			 'height'            => $size,
			 'maintain_ratio'    => TRUE
			);

			$this->load->library('image_lib', $config);
			$this->image_lib->resize();
			$this->image_lib->clear();
			list($current_width, $current_height, $type, $attr) = getimagesize($data["full_path"]);

			$config = array(
				'maintain_ratio'    => false,
				'source_image'      => $data["full_path"],
				'width'             => $target_thumb_size,
				'height'            => $target_thumb_size,
				'x_axis'            => ($current_width - $target_thumb_size) / 2,
				'y_axis'            => ($current_height - $target_thumb_size) / 2
			);

			$this->image_lib->initialize($config);
			$this->image_lib->crop();
			$resize = $this->image_lib->display_errors();

			$this->load->model("generic_mo");
			if ($brand_id)
				$node = $this->generic_mo->curl_with_error("http://192.241.222.166:8998/update_merchant_brand?account_id=" . urlencode($this->account_id) . "&brand_id=" . urlencode($brand_id) . "&brand_name=" . urlencode($brand_name) . "&brand_logo=" . urlencode($brand_logo) . "&api_token=" . urlencode($token));	
			else 
				$node = $this->generic_mo->curl_with_error("http://192.241.222.166:8998/add_merchant_brand?account_id=" . urlencode($this->account_id) . "&brand_name=" . urlencode($brand_name) . "&brand_logo=" . urlencode($brand_logo) . "&api_token=" . urlencode($token));
		}

		// Output
		if ($node["out"] === false) {
			if ($data["file_name"])
				unlink("./temp/brandlogo/" . $data["file_name"]);
			echo json_encode(array(
				"status" => "error",
				"message" => $node["err"],
				"statusCode" => 503
				));
			$this->output->set_status_header('503');
		}
		else if ($node["info"]['http_code'] != 200) {
			if ($data["file_name"])
				unlink("./temp/brandlogo/" . $data["file_name"]);
			echo json_encode(array(
				"status" => "error",
				"file" => $uploaded ? "/temp/brandlogo/" . $data["file_name"] : $brand_logo, 
				"message" => $node["out"],
				"statusCode" => $node["info"]['http_code']
				));
			$this->output->set_status_header($node["info"]['http_code']);
		}
		else {
			if ($uploaded)
				$this->session->set_userdata("/temp/brandlogo/" . $data["file_name"], true);
			echo json_encode(array(
				"status" => "success",
				"resize" => $resize,
				"message" => "Brand saved.",
				"file" => $uploaded ? "/temp/brandlogo/" . $data["file_name"] : $brand_logo,
				"statusCode" => 200,
				"node" => $node
				));
		}
	}

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */