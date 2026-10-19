<?php namespace AltDesign\AltCookiesAddon\Support;

use RuntimeException;

/**
 * Class OffSiteRedirect
 *
 * Thrown from the scanner's redirect callback to stop a page being followed to a
 * different host.
 *
 * @package  AltDesign\AltCookiesAddon
 * @license  Copyright (C) Alt Design Limited - All Rights Reserved - licensed under the MIT license
 * @link     https://alt-design.net
 */
class OffSiteRedirect extends RuntimeException
{
}
