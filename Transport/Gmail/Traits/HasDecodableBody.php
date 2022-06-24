<?php

namespace Symfony\Component\Mailer\Bridge\GmailApi\Transport\Gmail\Traits;


trait HasDecodableBody
{

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function getDecodedBody($content)
	{
		$content = str_replace('_', '/', str_replace('-', '+', $content));

		return base64_decode($content);
	}

}
