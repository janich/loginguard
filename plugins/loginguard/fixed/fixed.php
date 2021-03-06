<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

// Minimum PHP version check
if (!version_compare(PHP_VERSION, '5.4.0', '>='))
{
	return;
}

/**
 * Work around the very broken and completely defunct eAccelerator on PHP 5.4 (or, worse, later versions).
 */
if (function_exists('eaccelerator_info'))
{
	$isBrokenCachingEnabled = true;

	if (function_exists('ini_get') && !ini_get('eaccelerator.enable'))
	{
		$isBrokenCachingEnabled = false;
	}

	if ($isBrokenCachingEnabled)
	{
		/**
		 * I know that this define seems pointless since I am returning. This means that we are exiting the file and
		 * the plugin class isn't defined, so Joomla cannot possibly use it.
		 *
		 * Well, that is how PHP works. Unfortunately, eAccelerator has some "novel" ideas about how to go about it.
		 * For very broken values of "novel". What does it do? It ignores the return and parses the plugin class below.
		 *
		 * You read that right. It ignores ALL THE CODE between here and the class declaration and parses the
		 * class declaration. Therefore the only way to actually NOT load the  plugin when you are using it on a
		 * server where an irresponsible sysadmin has installed and enabled eAccelerator (IT'S END OF LIFE AND BROKEN
		 * PER ITS CREATORS FOR CRYING OUT LOUD) is to define a constant and use it to return from the constructor
		 * method, therefore forcing PHP to return null instead of an object. This prompts Joomla to not do anything
		 * with the plugin.
		 */
		if (!defined('AKEEBA_EACCELERATOR_IS_SO_BORKED_IT_DOES_NOT_EVEN_RETURN'))
		{
			define('AKEEBA_EACCELERATOR_IS_SO_BORKED_IT_DOES_NOT_EVEN_RETURN', 3245);
		}

		return;
	}
}

// Make sure Akeeba LoginGuard is installed
if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_loginguard'))
{
	return;
}

// Load FOF
if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	return;
}

/**
 * Akeeba LoginGuard Plugin for Two Step Verification method "Fixed"
 *
 * Requires a static string (password), different for each user. It effectively works as a second password. This is NOT
 * to be used on production sites. It serves as a demonstration plugin and as a template for developers to create their
 * own custom Two Step Verification plugins.
 */
class PlgLoginguardFixed extends JPlugin
{
	/**
	 * The TFA method name handled by this plugin
	 *
	 * @var   string
	 */
	private $tfaMethodName = 'fixed';

	/**
	 * Constructor. Loads the language files as well.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 */
	public function __construct($subject, array $config = array())
	{
		/**
		 * Required to work around eAccelerator on PHP 5.4 and later.
		 *
		 * PUBLIC SERVICE ANNOUNCEMENT: eAccelerator IS DEFUNCT AND INCOMPATIBLE WITH PHP 5.4 AND ANY LATER VERSION. If
		 * you have it enabled on your server go ahead and uninstall it NOW. It's officially dead since 2012. Thanks.
		 */
		if (defined('AKEEBA_EACCELERATOR_IS_SO_BORKED_IT_DOES_NOT_EVEN_RETURN'))
		{
			return;
		}

		parent::__construct($subject, $config);

		$this->loadLanguage();
	}

	/**
	 * Gets the identity of this TFA method
	 *
	 * @return  array
	 */
	public function onLoginGuardTfaGetMethod()
	{
		$helpURL = $this->params->get('helpurl', 'https://github.com/akeeba/loginguard/wiki/Fixed-Code');

		return array(
			// Internal code of this TFA method
			'name'          => $this->tfaMethodName,
			// User-facing name for this TFA method
			'display'       => JText::_('PLG_LOGINGUARD_FIXED_LBL_DISPLAYEDAS'),
			// Short description of this TFA method displayed to the user
			'shortinfo'     => JText::_('PLG_LOGINGUARD_FIXED_LBL_SHORTINFO'),
			// URL to the logo image for this method
			'image'         => 'media/plg_loginguard_fixed/images/fixed.svg',
			// Are we allowed to disable it?
			'canDisable'    => true,
			// Are we allowed to have multiple instances of it per user?
			'allowMultiple' => false,
			// URL for help content
			'help_url' => $helpURL,
		);
	}

	/**
	 * Returns the information which allows LoginGuard to render the captive TFA page. This is the page which appears
	 * right after you log in and asks you to validate your login with TFA.
	 *
	 * @param   stdClass  $record  The #__loginguard_tfa record currently selected by the user.
	 *
	 * @return  array
	 */
	public function onLoginGuardTfaCaptive($record)
	{
		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return array();
		}

		$helpURL = $this->params->get('helpurl', 'https://github.com/akeeba/loginguard/wiki/Fixed-Code');

		return array(
			// Custom HTML to display above the TFA form
			'pre_message'  => JText::_('PLG_LOGINGUARD_FIXED_LBL_PREMESSAGE'),
			// How to render the TFA code field. "input" (HTML input element) or "custom" (custom HTML)
			'field_type'   => 'input',
			// The type attribute for the HTML input box. Typically "text" or "password". Use any HTML5 input type.
			'input_type'   => 'password',
			// Placeholder text for the HTML input box. Leave empty if you don't need it.
			'placeholder'  => JText::_('PLG_LOGINGUARD_FIXED_LBL_PLACEHOLDER'),
			// Label to show above the HTML input box. Leave empty if you don't need it.
			'label'        => JText::_('PLG_LOGINGUARD_FIXED_LBL_LABEL'),
			// Custom HTML. Only used when field_type = custom.
			'html'         => '',
			// Custom HTML to display below the TFA form
			'post_message' => JText::_('PLG_LOGINGUARD_FIXED_LBL_POSTMESSAGE'),
			// Should I hide the submit button? Useful if you need to render your own buttons or use a method which is meant to auto-submit upon doing a certain action.
			'hide_submit'        => true,
			// URL for help content
			'help_url'     => $helpURL,
		);
	}

	/**
	 * Returns the information which allows LoginGuard to render the TFA setup page. This is the page which allows the
	 * user to add or modify a TFA method for their user account. If the record does not correspond to your plugin
	 * return an empty array.
	 *
	 * @param   stdClass  $record  The #__loginguard_tfa record currently selected by the user.
	 *
	 * @return  array
	 */
	public function onLoginGuardTfaGetSetup($record)
	{
		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return array();
		}

		// Load the options from the record (if any)
		$options = $this->_decodeRecordOptions($record);
		$helpURL = $this->params->get('helpurl', 'https://github.com/akeeba/loginguard/wiki/Fixed-Code');

		/**
		 * Return the parameters used to render the GUI.
		 *
		 * Some TFA methods need to display a different interface before and after the setup. For example, when setting
		 * up Google Authenticator or a hardware OTP dongle you need the user to enter a TFA code to verify they are in
		 * possession of a correctly configured device. After the setup is complete you don't want them to see that
		 * field again. In the first state you could use the tabular_data to display the setup values, pre_message to
		 * display the QR code and field_type=input to let the user enter the TFA code. In the second state do the same
		 * BUT set field_type=custom, set html='' and show_submit=false to effectively hide the setup form from the
		 * user.
		 */
		return array(
			// Default title if you are setting up this TFA method for the first time
			'default_title'  => JText::_('PLG_LOGINGUARD_FIXED_LBL_DEFAULTTITLE'),
			// Custom HTML to display above the TFA setup form
			'pre_message'    => JText::_('PLG_LOGINGUARD_FIXED_LBL_SETUP_PREMESSAGE'),
			// Heading for displayed tabular data. Typically used to display a list of fixed TFA codes, TOTP setup parameters etc
			'table_heading'  => '',
			// Any tabular data to display (label => custom HTML). See above
			'tabular_data'   => array(),
			// Hidden fields to include in the form (name => value)
			'hidden_data'    => array(),
			// How to render the TFA setup code field. "input" (HTML input element) or "custom" (custom HTML)
			'field_type'     => 'input',
			// The type attribute for the HTML input box. Typically "text" or "password". Use any HTML5 input type.
			'input_type'     => 'password',
			// Pre-filled value for the HTML input box. Typically used for fixed codes, the fixed YubiKey ID etc.
			'input_value'    => $options->fixed_code,
			// Placeholder text for the HTML input box. Leave empty if you don't need it.
			'placeholder'    => JText::_('PLG_LOGINGUARD_FIXED_LBL_PLACEHOLDER'),
			// Label to show above the HTML input box. Leave empty if you don't need it.
			'label'          => JText::_('PLG_LOGINGUARD_FIXED_LBL_LABEL'),
			// Custom HTML. Only used when field_type = custom.
			'html'           => '',
			// Should I show the submit button (apply the TFA setup)? Only applies in the Add page.
			'show_submit'    => true,
			// onclick handler for the submit button (apply the TFA setup)?
			'submit_onclick' => '',
			// Custom HTML to display below the TFA setup form
			'post_message'   => JText::_('PLG_LOGINGUARD_FIXED_LBL_SETUP_POSTMESSAGE'),
			// URL for help content
			'help_url' => $helpURL,
		);
	}

	/**
	 * Parse the input from the TFA setup page and return the configuration information to be saved to the database. If
	 * the information is invalid throw a RuntimeException to signal the need to display the editor page again. The
	 * message of the exception will be displayed to the user. If the record does not correspond to your plugin return
	 * an empty array.
	 *
	 * @param   stdClass  $record  The #__loginguard_tfa record currently selected by the user.
	 * @param   JInput    $input   The user input you are going to take into account.
	 *
	 * @return  array  The configuration data to save to the database
	 *
	 * @throws  RuntimeException  In case the validation fails
	 */
	public function onLoginGuardTfaSaveSetup($record, JInput $input)
	{
		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return array();
		}

		// Load the options from the record (if any)
		$options = $this->_decodeRecordOptions($record);

		// Merge with the submitted form data
		$code = $input->get('code', $options->fixed_code, 'raw');

		// Make sure the code is not empty
		if (empty($code))
		{
			throw new RuntimeException(JText::_('PLG_LOGINGUARD_FIXED_ERR_EMPTYCODE'));
		}

		// Return the configuration to be serialized
		return array(
			'fixed_code' => $code
		);
	}

	/**
	 * Validates the Two Factor Authentication code submitted by the user in the captive Two Step Verification page. If
	 * the record does not correspond to your plugin return FALSE.
	 *
	 * @param   stdClass  $record  The TFA method's record you're validatng against
	 * @param   JUser     $user    The user record
	 * @param   string    $code    The submitted code
	 *
	 * @return  bool
	 */
	public function onLoginGuardTfaValidate($record, JUser $user, $code)
	{
		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return false;
		}

		// Load the options from the record (if any)
		$options = $this->_decodeRecordOptions($record);

		// Double check the TFA method is for the correct user
		if ($user->id != $record->user_id)
		{
			return false;
		}

		// Check the TFA code for validity
		return JCrypt::timingSafeCompare($options->fixed_code, $code);
	}

	/**
	 * Decodes the options from a #__loginguard_tfa record into an options object.
	 *
	 * @param   stdClass  $record
	 *
	 * @return  stdClass
	 */
	private function _decodeRecordOptions($record)
	{
		$options = array(
			'fixed_code' => ''
		);

		if (!empty($record->options))
		{
			$recordOptions = $record->options;

			if (is_string($recordOptions))
			{
				$recordOptions = json_decode($recordOptions, true);
			}

			$options = array_merge($options, $recordOptions);
		}

		return (object) $options;
	}
}
