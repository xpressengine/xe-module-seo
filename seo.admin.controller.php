<?php
class seoAdminController extends seo
{
	function procSeoAdminSaveSetting()
	{
		$oModuleController = getController('module');

		$vars = Context::getRequestVars();
		$config = $this->getConfig();

		if ($vars->setting_section == 'general') {
			// 기본 설정
			$config->use_optimize_title = $vars->use_optimize_title;
			$config->site_name = $vars->site_name;
			$config->site_slogan = $vars->site_slogan;
			$config->site_description = $vars->site_description;
			$config->site_keywords = $vars->site_keywords;
			if ($vars->site_image) {
				$path = _XE_PATH_ . 'files/attach/site_image/';
				$ext = array_pop(explode('.', $vars->site_image['name']));
				$timestamp = time();
				$filename = "site_image.{$timestamp}.{$ext}";
				FileHandler::copyFile($vars->site_image['tmp_name'], $path . $filename);
				$config->site_image = $filename;
			}
		} elseif ($vars->setting_section == 'analytics') {
			// analytics

			// Google
			$config->ga_id = trim($vars->ga_id);
			$config->ga_except_admin = $vars->ga_except_admin;
			$config->ga_track_subdomain = $vars->ga_track_subdomain;

			// Naver
			$config->na_id = trim($vars->na_id);
			$config->na_except_admin = $vars->na_except_admin;
		}

		$config->site_image_url = NULL;

		$oModuleController->updateModuleConfig('seo', $config);

		$this->setMessage('success_updated');
		if (Context::get('success_return_url')) {
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
	}
}
/* !End of file */
