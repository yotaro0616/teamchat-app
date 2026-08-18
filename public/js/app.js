/*
 * 文字数カウンタ。
 *
 * アプリで JavaScript を使う範囲は screens.md 4章 と questions.md「自分で決めること」で
 * 「上限のある入力欄の追随型カウンタ」と「それに連動する送信・実行系ボタンの活性・非活性
 * （投稿欄の送信、検索、チャンネル削除の確認）」に限定してある。
 * いま扱っているのは、新規登録（SC-02）の表示名、チャンネルを作る／編集する（SC-04・SC-06）の
 * チャンネル名と説明のカウンタと、チャンネル削除の確認ボタンの活性・非活性。
 * 「登録する」「作成する」「保存する」は活性・非活性の対象に挙がっていないため、常に押せるままにする。
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

    /*
     * チャンネル削除の確認（SC-06）。
     *
     * 入力がチャンネル名と完全に一致するまで「削除する」を押せないままにする
     * （design-guide.md §4「押せない」／screens.md 4章）。
     * これは押し間違いを減らすための表示上の仕掛けで、一致の確認はサーバ側でも必ず再検証する
     * （behavior.md 3章）。JSを無効にしていても、初期状態の disabled のままなので誤って消えることはない。
     */
    document.querySelectorAll('[data-confirm-input]').forEach(function (input) {
        var button = document.getElementById(input.dataset.confirmInput);

        if (!button) {
            return;
        }

        var expected = input.dataset.confirmValue;

        var update = function () {
            button.disabled = input.value !== expected;
        };

        input.addEventListener('input', update);
        update();
    });
});
