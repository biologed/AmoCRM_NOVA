
<html>
	<head>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<title>Test NOVA</title>
	</head>
	<body>
	<script>
	$(function(){
		$('#name').val('');
		$('#email').val('');
		$('#phone').val('');
		setTimeout(function() {window.location.reload();}, 3600000);

		// $('#phone').keyup(function() {
		// 	$('#name').attr('disabled', true);
		// 	$('#name').val('');
		// 	if($('#email').val().trim().length == 0 && $('#phone').val().trim().length == 0) {
		// 		$('#name').attr('disabled', false);
		// 	}
		// });
		// $('#email').keyup(function() {
		// 	$('#name').attr('disabled', true);
		// 	$('#name').val('');
		// 	if($('#email').val().trim().length == 0 && $('#phone').val().trim().length == 0) {
		// 		$('#name').attr('disabled', false);
		// 	}
		// });

		$.ajax({
			url: '/auth.php',
			method: 'get',
			dataType: 'html',
			data: $(this).serialize(),
			success: function(data){
				$('#message_about_auth').html(data);
			}
		});

		$("#send").click(function(){
			let id = $('#id').val(),
			name = $('#name').val().trim(),
			email = $('#email').val().trim(),
			phone = $('#phone').val().trim();
			if(name.length == 0 && email.length == 0 && phone.length == 0) {
				alert('Поля не должны быть пустыми')
			} else {
				$.ajax({
					url: '/handler.php',
					method: 'get',
					dataType: 'html',
					data: {'name':name,'email':email,'phone':phone},
					success: function(data){
						console.log(data);
						data = JSON.parse(data);
						console.log(data);
						if(!$.isArray(data)) {
							if(confirm(data)) {
								console.log('yes');
								if(name != '' && email != '' && phone != '') {
									$.ajax({
										url: '/handler.php?create=create',
										method: 'get',
										dataType: 'html',
										data: {'name':name,'email':email,'phone':phone},
										success: function(data){
											console.log(data);
											data = JSON.parse(data);
											console.log(data);
										}
									});
								} else {
									alert('Поля не должны быть пустыми')
								}
							}
						} else {
							$.each(data, function(){
								let id_contact = this.id_contact,
									name_contact = this.name_contact,
									phone_contact = this.phone_contact,
									email_contact = this.email_contact;
									id = id_contact;
									$('#message').append('<tr><td>'+id_contact+'</td><td>'+name_contact+'</td><td>'+email_contact+'</td><td>'+phone_contact+'</td></tr>');								
							});
							if(id != '' && name != '' && email != '' && phone != '') {
								$.ajax({
									url: '/handler.php?update=update',
									method: 'get',
									dataType: 'html',
									data: {'id':id,'name':name,'email':email,'phone':phone},
									success: function(data){
										console.log(data);
										data = JSON.parse(data);
										console.log(data);
									}
								});
							}
						}
					}
				});
			}
		});
	});
	</script>

	<div id="message_about_auth"></div>
	<table>
		<tbody id="message">
			<tr>
				<td><b>ID</b></td><td><b>NAME</b></td><td><b>EMAIL</b></td><td><b>PHONE</b></td>
			</tr>
			
		</tbody>
	</table>

	<div class="form_container">
		<input id="id" type="hidden" name="name">
		ФИО<input id="name" type="text" name="name">
		Почта<input id="email" type="text" name="email">
		Телефон<input id="phone" type="text" name="phone">
		<input id="send" type="button" value="Передать данные">
	</div>

	</body>
</html>