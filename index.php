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
		$.ajax({
			url: '/auth.php',
			method: 'get',
			dataType: 'json',
			success: function(data){
				alert(data['data']);
			}
		});

		$("#create-task").click(function() {
			$.ajax({
				url: '/handler.php?get_links=get_links',
				method: 'get',
				dataType: 'json',
				success: function(data) {
					if(data['status'] == 'success') {
						alert(data['data']);
						$.ajax({
							url: '/handler.php?create_tasks=create_tasks',
							method: 'get',
							dataType: 'html',
							data: $(this).serialize(),
							success: function(data) {
								console.log(data);
							}
						});
					} else {
						alert(data['data']);
					}
				}
			});
		});

		$("#send").click(function() {
			let id = $('#id_contact-0').data('value'),
			name = $('#name').val().trim(),
			email = $('#email').val().trim(),
			phone = $('#phone').val().trim();
			if(name.length != 0 || email.length != 0 || phone.length != 0) {
				$.ajax({
					url: '/handler.php',
					method: 'get',
					dataType: 'json',
					data: {'name':name, 'email':email, 'phone':phone},
					success: function(data) {
						console.log(data);
						if(data['status'] == 'success') {
							data = JSON.parse(data['data']);
							$.each(data, function(key, value) {
								let id_contact = this.id_contact,
									name_contact = this.name_contact,
									phone_contact = this.phone_contact,
									email_contact = this.email_contact;
									$('#message').append('<tr><td id="id_contact-'+key+'" data-value="' + id_contact + '">' + id_contact + '</td><td>' + name_contact + '</td><td>' + email_contact + '</td><td>' + phone_contact + '</td></tr>');								
							});
							if(id != '' && name != '' && email != '' && phone != '') {
								console.log(id);
								$.ajax({
									url: '/handler.php?update=update',
									method: 'get',
									dataType: 'json',
									data: {'id':id, 'name':name, 'email':email, 'phone':phone},
									success: function(data){
										console.log(data);
										alert(data['data']);
									}
								});
							}
						}
						else if(data['status'] == 'fail') {
							if(confirm(data['data'])) {
								if(name.length != 0 && email.length != 0 && phone.length != 0) {
									$.ajax({
										url: '/handler.php?create=create',
										method: 'get',
										dataType: 'json',
										data: {'name':name, 'email':email, 'phone':phone},
										success: function(data){
											alert(data['data']);
										}
									});
								} else {
									alert('?????? ???????? ???????????? ???????? ??????????????????????');
								}
							}
						}
						else if(data['status'] == 'error') {
							alert(data['data']);
						}
					}
				});
			} else {
				alert('???????? ???? ???????????? ???????? ??????????????');
			}
		});
	});
	</script>

	<table>
		<tbody id="message">
			<tr>
				<td><b>ID</b></td><td><b>NAME</b></td><td><b>EMAIL</b></td><td><b>PHONE</b></td>
			</tr>
			
		</tbody>
	</table>

	<div class="form_container">
		<input id="id" type="hidden" name="name">
		??????<input id="name" type="text" name="name">
		??????????<input id="email" type="text" name="email">
		??????????????<input id="phone" type="text" name="phone">
		<input id="send" type="button" value="???????????????? ????????????">
		<input id="create-task" type="button" value="?????????????? ????????????">
	</div>

	</body>
</html>