/*
 * 文字数カウンタ。
 *
 * アプリで JavaScript を使う範囲は screens.md 4章 と questions.md「自分で決めること」で
 * 「上限のある入力欄の追随型カウンタ」と「それに連動する送信・実行系ボタンの活性・非活性
 * （投稿欄の送信、検索、チャンネル削除の確認）」に限定してある。
 * いま扱っているのは、新規登録（SC-02）の表示名、チャンネルを作る／編集する（SC-04・SC-06）の
 * チャンネル名と説明のカウンタ、チャンネル削除の確認ボタンの活性・非活性、そして
 * チャンネル（SC-05）の投稿欄とメッセージ編集欄のカウンタ＋送信・保存ボタンの活性・非活性。
 * 「登録する」「作成する」「保存する（チャンネル編集）」は活性・非活性の対象に挙がっていないため、
 * 常に押せるままにする（メッセージ本文の「送信」「保存」だけが対象。screens.md 4章の追記）。
 * 上限を超えた入力はサーバ側の入力チェックがはじく（screens.md 4章）。
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-counter]').forEach(function (input) {
        var output = document.getElementById(input.dataset.counter);

        if (!output) {
            return;
        }

        var max = Number(input.dataset.counterMax);
        // メッセージ本文だけ「0 / 1,000 文字」と単位が付く（mockup/channel-show.html）。
        // 他の欄は「0 / 50」で単位を付けない（mockup/channel-new.html）。
        var unit = input.dataset.counterUnit || '';
        // 空欄・上限超過のあいだ押せない状態にするボタン（screens.md 4章）。指定がなければ何もしない。
        var submit = input.dataset.counterSubmit
            ? document.getElementById(input.dataset.counterSubmit)
            : null;

        var format = function (value) {
            // 「1,000」のように3桁区切りで出す（mockup/channel-show.html・design-guide.md §4）。
            return value.toLocaleString('en-US');
        };

        var update = function () {
            // PHP 側の max ルールと数え方をそろえるため、コードポイントで数える。
            // value.length だと UTF-16 の単位になり、絵文字などを2文字と数えてしまう。
            var length = Array.from(input.value).length;

            output.textContent = format(length) + ' / ' + format(max) + unit;
            output.classList.toggle('is-over', length > max);

            if (submit) {
                submit.disabled = length === 0 || length > max;
            }
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
