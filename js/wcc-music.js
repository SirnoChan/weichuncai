/*
 * wcc-music.js — 伪春菜音乐跨页连续播放
 *
 * 依赖: js/common.js 的 smWCC 对象（看板娘浮窗）、后台配置的 wccMusicList（音乐列表）
 *
 * 原理:
 * - 播放状态（曲目/进度/是否播放中）实时写入 sessionStorage —— 同一标签页内
 *   跨页面导航时 sessionStorage 保留，新页面加载后立即恢复播放（续播点一致）
 * - 集成 MediaSession API —— 系统媒体控制/锁屏可显示曲目并遥控
 * - 页面加载后自动尝试续播；若浏览器拦截自动播放，看板娘气泡提示"点击继续"
 *
 * 首次开启: 点击看板娘菜单的「音乐」项开始播放，之后全站跳转自动续播。
 */
'use strict';

var smWCCMusic = (function (jQuery) {
    var STATE_KEY = 'wcc-music-state';
    var audio = null;
    var list = (typeof wccMusicList !== 'undefined') ? wccMusicList : [];
    var idx = 0;

    var saveState = function () {
        if (!audio) return;
        try {
            sessionStorage.setItem(STATE_KEY, JSON.stringify({
                playing: !audio.paused,
                current: idx,
                time: audio.currentTime,
                list: list
            }));
        } catch (e) { /* sessionStorage 不可用时静默 */ }
    };

    var restoreState = function () {
        try {
            var raw = sessionStorage.getItem(STATE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) { return null; }
    };

    var updateMediaSession = function (track) {
        if (!('mediaSession' in navigator) || !track) return;
        try {
            navigator.mediaSession.metadata = new MediaMetadata({
                title: track.name,
                artist: '伪春菜'
            });
            navigator.mediaSession.setActionHandler('play', function () { audio.play(); });
            navigator.mediaSession.setActionHandler('pause', function () { audio.pause(); saveState(); });
            navigator.mediaSession.setActionHandler('previoustrack', function () { playIndex(idx - 1); });
            navigator.mediaSession.setActionHandler('nexttrack', function () { playIndex(idx + 1); });
        } catch (e) { /* 老浏览器忽略 */ }
    };

    var playIndex = function (i, resumeAt) {
        if (!list.length) return;
        idx = ((i % list.length) + list.length) % list.length;
        var track = list[idx];
        if (!audio) {
            audio = new Audio();
            audio.addEventListener('ended', function () { playIndex(idx + 1); });
            audio.addEventListener('timeupdate', function () {
                if (Math.floor(audio.currentTime) % 5 === 0) saveState();
            });
            audio.addEventListener('pause', saveState);
        }
        audio.src = track.url;
        var p = audio.play();
        if (p && p.catch) {
            p.catch(function () {
                // 浏览器拦截自动播放：看板娘提示，点击恢复
                if (typeof smWCC !== 'undefined' && smWCC.say) {
                    smWCC.say('音乐被浏览器拦下来了，点我一下继续放哦～');
                }
                jQuery(document).one('click', function () {
                    audio.play().catch(function () {});
                    saveState();
                });
            });
        }
        saveState();
        updateMediaSession(track);
        if (resumeAt) {
            audio.addEventListener('loadedmetadata', function once() {
                if (resumeAt > 0 && resumeAt < audio.duration) audio.currentTime = resumeAt;
                audio.removeEventListener('loadedmetadata', once);
            });
        }
    };

    return {
        init: function () {
            if (!list.length) return;
            var st = restoreState();
            if (st && st.playing && st.list && st.list.length) {
                list = st.list;
                playIndex(st.current, st.time);
            }
        },
        toggle: function () {
            if (!audio) { playIndex(0); return; }
            if (audio.paused) { audio.play(); } else { audio.pause(); saveState(); }
        },
        next: function () { playIndex(idx + 1); },
        prev: function () { playIndex(idx - 1); },
        state: function () {
            return audio ? { playing: !audio.paused, current: idx, time: audio.currentTime } : null;
        }
    };
})(jQuery);

// 页面就绪后启动恢复（伪春菜浮窗已就位）
jQuery(document).ready(function () {
    if (typeof smWCCMusic !== 'undefined' && typeof smWCC !== 'undefined') {
        smWCCMusic.init();
    }
});
