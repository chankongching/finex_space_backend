(function($) {
  "use strict";
  
  $(function(){
    // ===============================
    // Bootstrap-datetimepicker plugin
    // ===============================
    // Enables autoclose
    $.fn.datetimepicker.defaults.autoclose = true;
    $.fn.datetimepicker.defaults.pickerPosition = "bottom-left";

    // Bootstrap-datetimepicker examples
    $("#datetimepicker-example1").datetimepicker({
      format: "yyyy-mm-dd hh:ii",
      pickerPosition: "bottom-right"
    });

    $("#datetimepicker-example2").datetimepicker({
      format: "dd MM yyyy - hh:ii"
    });

    // Meridian format
    $("#datetimepicker-example3").datetimepicker({
      showMeridian: true,
      todayBtn: true
    });

    // Inline example
    $("#datetimepicker-example4").datetimepicker();

    // Bootstrap-datepicker plugin
    $.fn.datepicker.defaults.autoclose = true;
    $('#datepicker').datepicker();


    // ============================
    // Bootstrap-daterangepicker plugin
    // ============================
    // Bootstrap-daterangepicker examples
    $("#daterangepicker-example1").daterangepicker({
      applyClass: 'btn-primary',
      timePicker: true,
      timePickerIncrement: 30,
      locale: {
          format: "MM/DD/YYYY h:mm A"
      }
    });

    $("#daterangepicker-example2").daterangepicker({
        singleDatePicker: true,
        showDropdowns: true
    }, 
    function(start, end, label) {
        var years = moment().diff(start, 'years');
        alert("You are " + years + " years old.");
    });

    // Predefined ranges
    function cb3(start, end) {
      $("#daterangepicker-example3 span").html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
    }
    cb3(moment().subtract(29, 'days'), moment());

    $("#daterangepicker-example3").daterangepicker({
      applyClass: 'btn-primary',
      ranges: {
         'Today': [moment(), moment()],
         'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
         'Last 7 Days': [moment().subtract(6, 'days'), moment()],
         'Last 30 Days': [moment().subtract(29, 'days'), moment()],
         'This Month': [moment().startOf('month'), moment().endOf('month')],
         'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
      }
    }, cb3);

    // Predefined ranges button style
    function cb4(start, end) {
      $("#daterangepicker-example4 span").html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
    }
    cb4(moment().subtract(29, 'days'), moment());

    $("#daterangepicker-example4").daterangepicker({
      applyClass: 'btn-primary',
      ranges: {
         'Today': [moment(), moment()],
         'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
         'Last 7 Days': [moment().subtract(6, 'days'), moment()],
         'Last 30 Days': [moment().subtract(29, 'days'), moment()],
         'This Month': [moment().startOf('month'), moment().endOf('month')],
         'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
      }
    }, cb4);

    // ============================
    // Bootstrap-timepicker plugin
    // ============================
    $.fn.timepicker.defaults.icons.up = "fa fa-angle-up";
    $.fn.timepicker.defaults.icons.down = "fa fa-angle-down";

    // Showing popup when clicked on input, not only addon
    $("#timepicker-example1, #timepicker-example2").on("focus", function(){
      $(this).timepicker("showWidget");
    })

    // Bootstrap-timepicker examples
    $('#timepicker-example1, #timepicker-example2').timepicker();

    // Without a template
    $('#timepicker-example3').timepicker({
      template: false,
      showInputs: false,
      minuteStep: 5
    });

    // ============================
    // Clockface plugin
    // ============================
    // Basic example
    $('#clockface-basic-example').clockface();

    // Readonly example
    $('#clockface-readonly-example').clockface({
      format: 'HH:mm',
      trigger: 'manual'
    });
    $('#clockface-readonly-example').closest('.input-group').find('.btn').click(function(e){   
      e.stopPropagation();
      $('#clockface-readonly-example').clockface('toggle');
    });

    // Inline example
    $('#clockface-inline-example').clockface({
      format: 'H:mm'
    }).clockface('show', '14:30');
  })
})(jQuery);  
