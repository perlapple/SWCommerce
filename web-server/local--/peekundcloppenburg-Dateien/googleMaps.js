function openGoogleMaps(area, lang, pucID)
{
	var url = '/fileadmin/_web/js/rb_googleMaps/popup.php?area=' + area  + '&lang=' + lang + "&pucID=" + pucID;
	window.open(
		url, '', 'height=600,width=850,dependent=yes,location=no,menubar=no,resizable=yes,status=no,toolbar=no,scrollbars=yes'
	);
}
