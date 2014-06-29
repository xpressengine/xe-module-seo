<?php
class seo extends ModuleObject
{
	public $SEO = array(
		'link' => array(),
		'meta' => array()
	);

	protected $canonical_url;

	private $triggers = array(
		array('display', 'seo', 'controller', 'triggerBeforeDisplay', 'before')
	);

	public function getConfig()
	{
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('seo');
		if (!$config) $config = new stdClass;
		if (!$config->use_optimize_title) $config->use_optimize_title = 'N';
		if (!$config->ga_except_admin) $config->ga_except_admin = 'N';
		if (!$config->ga_track_subdomain) $config->ga_track_subdomain = 'N';
		if ($config->site_image) 
		{
			$config->site_image_url = Context::get('request_uri') . 'files/attach/site_image/' . $config->site_image;
		}

		return $config;
	}

	public function addMeta($property, $content)
	{
		if (!$content) return;

		$oModuleController = getController('module');
		$oModuleController->replaceDefinedLangCode($content);
		if (!in_array($property, array('og:url'))) {
			$content = htmlspecialchars($content);
			$content = str_replace(PHP_EOL, ' ', $content);
		}

		$this->SEO['meta'][] = array('property' => $property, 'content' => $content);
	}

	public function addLink($rel, $href)
	{
		if (!$href) return;

		$this->SEO['link'][] = array('rel' => $rel, 'href' => $href);
	}

	protected function applySEO()
	{
		$config = $this->getConfig();
		$logged_info = Context::get('logged_info');

		foreach ($this->SEO as $type => $list) {
			if (!$list || !count($list)) continue;

			foreach ($list as $val) {
				if ($type == 'meta') {
					Context::addHtmlHeader('<meta property="' . $val['property'] . '" content="' . $val['content'] . '" />');
				} elseif ($type == 'link') {
					Context::addHtmlHeader('<link rel="' . $val['rel'] . '" href="' . $val['href'] . '" />');
				}
			}
		}

		// Google Analytics
		if ($config->ga_id && !($config->ga_except_admin == 'Y' && $logged_info->is_admin == 'Y')) {
			$gaq_push = array();
			$gaq_push[] = '_gaq.push([\'_setAccount\', \'' . $config->ga_id . '\']);';
			if ($config->ga_track_subdomain) {
				$db_info = Context::getDBInfo();
				$default_url = parse_url($db_info->default_url);
				$gaq_push[] = '_gaq.push([\'_setDomainName\', \'' . $default_url['host'] . '\']);';
			}
			$gaq_push[] = '_gaq.push([\'_trackPageview\', \'' . str_replace(Context::get('request_uri'), '/', $this->canonical_url) . '\']);';
			$gaq_push = implode(PHP_EOL, $gaq_push);

			$ga_script = <<< GASCRIPT
			<!-- Google Analytics -->
			<script type="text/javascript">
				var _gaq = _gaq || [];
				{$gaq_push}
				(function() {
					var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
					ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
					var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				})();
			</script>
GASCRIPT;

			Context::addHtmlHeader($ga_script . PHP_EOL);
		}

		// Naver Analytics
		if ($config->na_id && !($config->na_except_admin == 'Y' && $logged_info->is_admin == 'Y')) {
			$wcs_add = array();
			$wcs_add[] = "wcs_add['wa'] = '{$config->na_id}';";
			$wcs_add = implode(' ', $wcs_add);

			$na_script = <<< NASCRIPT
			<!-- Naver Analytics -->
			<script type="text/javascript" src="http://wcs.naver.net/wcslog.js"></script> <script type="text/javascript"> if(!wcs_add) var wcs_add = {}; {$wcs_add} wcs_do(); </script>
NASCRIPT;
			Context::addHtmlHeader($na_script . PHP_EOL);
		}
	}

	function moduleInstall()
	{
		return new Object();
	}

	function checkUpdate()
	{
		$oModuleModel = getModel('module');

		foreach ($this->triggers as $trigger) {
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return TRUE;
		}

		return FALSE;
	}

	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		foreach ($this->triggers as $trigger) {
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) {
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return new Object(0, 'success_updated');
	}

	function moduleUninstall()
	{
		$oModuleController = getController('module');

		foreach ($this->triggers as $trigger) {
			$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}

		return new Object();
	}
}
/* !End of file */
