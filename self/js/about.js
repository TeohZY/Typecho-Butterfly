(()=>{
    function pageStyle() {
    const mangoAbout = document.getElementById("about-page")
    if (mangoAbout) {
        const bodyWrap = document.getElementById("body-wrap");
        bodyWrap.style.backgroundColor = '#FFF';
        const layout = document.getElementById("content-inner");
        layout.style.width = "90%";
        const hideAside = document.getElementsByClassName("hide-aside");
        hideAside[0].style.maxWidth = 'none';
        const page = document.getElementById("page");
        page.style.backgroundColor = "transparent !important";
        page.style.border = "none";
        page.style.boxShadow = "none";
        const pageTitle = document.getElementsByClassName("page-title");
        pageTitle[0].style.display = 'none';
    }
};

function initAboutPage() {
    fetch("https://v6-widget.51.la/v6/Jv1zicUd8PkfXdBP/quote.js")
        .then(res => res.text())
        .then(data => {
            let title = ["最近活跃", "今日人数", "今日访问", "昨日人数", "昨日访问", "本月访问", "总访问量"];
            let num = data.match(/(<\/span><span>).*?(\/span><\/p>)/g);

            num = num.map(el => {
                let val = el.replace(/(<\/span><span>)/g, "");
                let str = val.replace(/(<\/span><\/p>)/g, "");
                return str;
            });

            let statisticEl = document.getElementById("statistic");

            // 自定义不显示哪个或者显示哪个，如下为不显示 最近活跃访客 和 总访问量
            let statistic = [];
            for (let i = 0; i < num.length; i++) {
                if (!statisticEl) return;
                if (i == 0) continue;
                statisticEl.innerHTML +=
                    "<div><span>" + title[i] + "</span><span id=" + title[i] + ">" + num[i] + "</span></div>";
                queueMicrotask(() => {
                    statistic.push(
                        new CountUp(title[i], 0, num[i], 0, 2, {
                            useEasing: true,
                            useGrouping: true,
                            separator: ",",
                            decimal: ".",
                            prefix: "",
                            suffix: "",
                        })
                    );
                });
            }

            let statisticElement = document.querySelector(".about-statistic.author-content-item");
            function statisticUP() {
                // if (!statisticElement) return;
                const callback = (entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            for (let i = 0; i < num.length; i++) {
                                if (i == 0) continue;
                                queueMicrotask(() => {
                                    statistic[i - 1].start();
                                });
                            }
                            observer.disconnect(); // 停止观察元素，因为不再需要触发此回调
                        }
                    });
                };

                const options = {
                    root: null,
                    rootMargin: "0px",
                    threshold: 0
                };
                const observer = new IntersectionObserver(callback, options);
                observer.observe(statisticElement);
            }
            const selfInfoContentYear = new CountUp("selfInfo-content-year", 0, 1998, 0, 2, {
                useEasing: true,
                useGrouping: false,
                separator: ",",
                decimal: ".",
                prefix: "",
                suffix: "",
            });

            let selfInfoContentYearElement = document.querySelector(".author-content-item.selfInfo.single");
            function selfInfoContentYearUp() {
                if (!selfInfoContentYearElement) return;

                const callback = (entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            selfInfoContentYear.start();
                            observer.disconnect(); // 停止观察元素，因为不再需要触发此回调
                        }
                    });
                };

                const options = {
                    root: null,
                    rootMargin: "0px",
                    threshold: 0
                };
                const observer = new IntersectionObserver(callback, options);
                observer.observe(selfInfoContentYearElement);
            }

            selfInfoContentYearUp();
            statisticUP()
        });

    var pursuitInterval = null;
    pursuitInterval = setInterval(function () {
        const show = document.querySelector("span[data-show]");
        const next = show.nextElementSibling || document.querySelector(".first-tips");
        const up = document.querySelector("span[data-up]");

        if (up) {
            up.removeAttribute("data-up");
        }

        show.removeAttribute("data-show");
        show.setAttribute("data-up", "");

        next.setAttribute("data-show", "");
    }, 2000);

    document.addEventListener("pjax:send", function () {
        pursuitInterval && clearInterval(pursuitInterval);
    });

    var helloAboutEl = document.querySelector(".hello-about");
    helloAboutEl.addEventListener("mousemove", evt => {
        const mouseX = evt.offsetX;
        const mouseY = evt.offsetY;
        gsap.set(".cursor", {
            x: mouseX,
            y: mouseY,
        });

        gsap.to(".shape", {
            x: mouseX,
            y: mouseY,
            stagger: -0.1,
        });
    });
}
function pjax_reload() {
    pageStyle();
    if (typeof gsap === "object") {
        initAboutPage()
    } else {
        getScript("https://lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/gsap/3.9.1/gsap.min.js").then(initAboutPage);
    }
};
    document.addEventListener("pjax:complete", function () {
        pjax_reload();
    });

    pageStyle();
    
    if (typeof gsap === "object") {
        initAboutPage()
    } else {
        getScript("https://lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/gsap/3.9.1/gsap.min.js").then(initAboutPage);
    }
})();