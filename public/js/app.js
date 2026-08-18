/*
 * 文字数カウンタ。
 *
 * アプリで JavaScript を使う範囲は screens.md 4章 と questions.md「自分で決めること」で
 * 「上限のある入力欄の追随型カウンタ」と「それに連動する送信・実行系ボタンの活性・非活性
 * （投稿欄の送信、検索、チャンネル削除の確認）」に限定してある。
 * 認証の画面で該当するのは新規登録（SC-02）の表示名のカウンタだけなので、ここではそれだけを扱う。
 * 「登録する」ボタンは活性・非活性の対象に挙がっていないため、常に押せるままにする。
 * 上限を超えた入力はサーバ側の入力チェックがはじく（screens.md 4章）。
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-counter]').forEach(function (input) {
        var output = document.getElementById(input.dataset.counter);

        if (!output) {
            return;
        }

        var max = Number(input.dataset.counterMax);

        var update = function () {
            // PHP 側の max ルールと数え方をそろえるため、コードポイントで数える。
            // value.length だと UTF-16 の単位になり、絵文字などを2文字と数えてしまう。
            var length = Array.from(input.value).length;

            output.textContent = length + ' / ' + max;
            output.classList.toggle('is-over', length > max);
        };

        input.addEventListener('input', update);
        update();
    });
});
