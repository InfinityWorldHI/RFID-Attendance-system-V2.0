$(document).ready(function(){
  // Get Report passenger
  $(document).on('click', '#user_log', function(){
    
    var date_sel_start = $('#date_sel_start').val();
    var date_sel_end = $('#date_sel_end').val();
    var time_sel = $(".time_sel:checked").val();
    var time_sel_start = $('#time_sel_start').val();
    var time_sel_end = $('#time_sel_end').val();
    var card_sel = $('#card_sel option:selected').val();
    var dev_uid = $('#dev_sel option:selected').val();
    
    $.ajax({
      url: 'user_log_up.php',
      type: 'POST',
      data: {
        'log_date': 1,
        'date_sel_start': date_sel_start,
        'date_sel_end': date_sel_end,
        'time_sel': time_sel,
        'time_sel_start': time_sel_start,
        'time_sel_end': time_sel_end,
        'card_sel': card_sel,
        'dev_uid': dev_uid,
      },
      success: function(response){

        $('.up_info2').fadeIn(500);
        $('.up_info2').text("The Filter has been selected!");

        $('#Filter-export').modal('hide');
        setTimeout(function () {
            $('.up_info2').fadeOut(500);
        }, 5000);

        $.ajax({
          url: "user_log_up.php",
          type: 'POST',
          data: {
            'log_date': 1,
            'date_sel_start': date_sel_start,
            'date_sel_end': date_sel_end,
            'time_sel': time_sel,
            'time_sel_start': time_sel_start,
            'time_sel_end': time_sel_end,
            'dev_uid': dev_uid,
            'card_sel': card_sel,
            'select_date': 0,
          }
          }).done(function(data) {
          $('#userslog').html(data);
        });
      }
    });
  });
});