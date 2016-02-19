var serviceUrlCard = 'https://gwapi.demo.securenet.com/api/PreVault/Card';
var serviceUrlCheck = 'https://gwapi.demo.securenet.com/api/PreVault/Check';
var publicKey = 'default';


function setPublicKey(z) {
	publicKey = z;
return publicKey;
}

function tokenizeCard(x) {

	return jQuery.ajax({
		type: "POST",
		url: serviceUrlCard,
		data: JSON.stringify(x),
		contentType: "application/json",
		dataType: "json"
	})

}

function tokenizeCheck(x) {

	return jQuery.ajax({
		type: "POST",
		url: serviceUrlCheck,
		data: JSON.stringify(x),
		contentType: "application/json",
		dataType: "json"
	})

}