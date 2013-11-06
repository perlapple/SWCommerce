/***************************************************************
*  Copyright notice
*
*  (c) 2008 Alex Kellner <alexander.kellner@einpraegsam.net>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


function wt_socialbookmarks_add2favourite(Text, URL) {

	if (window.sidebar) { // firefox
		window.sidebar.addPanel(Text, URL, "");
	}
	
	else if(window.opera && window.print) { // opera
		var elem = document.createElement('a');
		elem.setAttribute('href', URL);
		elem.setAttribute('title', Text);
		elem.setAttribute('rel', 'sidebar');
		elem.click();
	}
	
	else if(document.all) { // ie
		window.external.AddFavorite(URL, Text);
	}
}