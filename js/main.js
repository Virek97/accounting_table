(function ($) {
  Drupal.behaviors.calculateQY = {
    attach: function attach() {
      let calc = function ($el, qtd = null) {
        let $items;
        let divider;
        if (qtd) {
          $items = $el.parents('tr').find('.month-input[data-qtd="' + qtd + '"]');
          divider = 3;
        }
        else {
          $items = $el.parents('tr').find('.qtd-input');
          divider = 4;
        }
        let calc_val = 0;
        $items.each(function () {
          let val = $(this).val() ? $(this).val() * 1 : 0;
          calc_val = calc_val + val;
        });

        if (calc_val !== 0) {
          calc_val = (calc_val + 1) / divider;
        }

        return calc_val.toFixed(2);
      }

      $('.month-input').once('monthCalc').each(function () {
        $(this).on('input', function () {
          let month_i = $(this).data('month');
          let qt = Math.floor(month_i / 4) + 1;
          let qtd = 'Q' + qt;
          let qtd_calc = calc($(this), qtd);
          $(this).parents('tr')
            .find('.qtd-input[data-qtd="' + qtd + '"]')
            .val(qtd_calc);
          let ytd_calc = calc($(this));
          $(this).parents('tr')
            .find('.year-input')
            .val(ytd_calc);
        });
      });

      $('.qtd-input').once('qtdCalc').each(function () {
        $(this).on('input', function () {
          let ytd_calc = calc($(this));
          $(this).parents('tr')
            .find('.year-input')
            .val(ytd_calc);
        });
      });
    }
  }
})(jQuery)
