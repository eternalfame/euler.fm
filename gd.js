class SVG {
    constructor(im_w, im_h) {
        this.content = `<?xml version="1.0" encoding="utf-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${im_w}" height="${im_h}">
        `;
    }

    drawCircle(className, x, y, radius, fillColor, strokeColor) {
        this.content += `<circle class="${className}" cx="${x}" cy="${y}" r="${radius}" style="fill: ${fillColor}; stroke: ${strokeColor}; stroke-width: 1"/>`;
    }

    drawText(x, y, text, fillColor) {
        this.content += `<text font-size="12" font="Open Sans" text-anchor="middle" x="${x}" y="${y}" style="fill: ${fillColor};">${text}</text>`;
    }

    drawLink(x, y, url, text, fillColor) {
        this.content += `<a href="${url}">`;
        this.drawText(x, y, text, fillColor);
        this.content += `</a>`;
    }

    output() {
        return this.content + '</svg>';
    }
}


/**
 * @param {number} r Radius of first circle
 * @param {number} R Radius of second circle
 * @param {number} d Distance between the centers of two circles
 * @return {number} Area of the intersection of two circles
 */
function intersectionAreaByDistance(r, R, d) {
    if (r === 0 || R === 0 || d === 0) return 0;

    const R2 = R * R;
    const r2 = r * r;
    const d2 = d * d;

    const part1 = R2 * Math.acos((R2 - r2 + d2) / (2.0 * d * R));
    const part2 = r2 * Math.acos((-R2 + r2 + d2) / (2.0 * d * r));
    const part3 = 0.5 * Math.sqrt((-R + r + d) * (R - r + d) * (R + r - d) * (R + r + d));

    return part1 + part2 - part3;
}

/**
 * @param {number} R1 Radius of first circle
 * @param {number} R2 Radius of second circle
 * @param {number} d Distance between the centers of two circles
 * @return {number} Length of the intersection between two circles
 */
function intersectionDistanceByRadius(R1, R2, d) {
    if (d === 0) {
        return 0;
    }
    
    if (d > R1 + R2 || d < Math.abs(R1 - R2)) {
        return R1;
    }

    return (Math.pow(R1, 2) - Math.pow(R2, 2) + Math.pow(d, 2)) / (2 * d);
}

/**
 * @param {number} area1 Area of first circle
 * @param {number} area2 Area of second circle
 * @param {number} areaIntersection Area of intersection between two circles
 * @return {number} Distance between the centers of two circles
 */
function distanceByIntersectionArea(area1, area2, areaIntersection) {
    if (area1 === areaIntersection && area2 === areaIntersection) {
        return 0
    }
    const r1 = Math.sqrt(area1 / Math.PI);
    const r2 = Math.sqrt(area2 / Math.PI);

    // Since the function cannot be reversed analytically, we need to find the value through an iterative approach.
    let distance = r1 + r2; // no intersection
    let possibleAreaIntersection = 0;
    let prev_diff = area1 > area2 ? 1 : -1;
    let step = 1.0;

    let diff = areaIntersection - possibleAreaIntersection;
    while (Math.abs(diff) > 0.1) {
        distance += (diff > 0 ? -step : step);
        possibleAreaIntersection = intersectionAreaByDistance(r1, r2, distance);
        diff = areaIntersection - possibleAreaIntersection;

        if (diff * prev_diff < 0) {
            step /= 2.0;
        }
        prev_diff = diff;
    }
    return distance
}

function makeImage(s1, s2, s3, artists1, artists2, artists3) {
    if (s1 === 0 || s2 === 0) {
        return "It seems one of the users didn't listen to anything in this period :(";
    }
    
    let invert = false;
    if (s1 < s2) {
        invert = true;
        [s1, s2] = [s2, s1];
        [artists1, artists2] = [artists2, artists1];
    }

    const r1 = Math.sqrt(s1 / Math.PI);
    const r2 = Math.sqrt(s2 / Math.PI);

    const min_r = Math.min(r1, r2);
    const max_r = Math.max(r1, r2);
    const dif = min_r / max_r;
    const zoom = 200.0 / r1;

    const distance = distanceByIntersectionArea(s1, s2, s3)

    const border = 5;
    
    const c1_d = 400.0;
    const c1_x = border + c1_d / 2;
    const c1_y = c1_x;
    const c2_x = Math.floor(c1_x + distance * zoom);
    const c2_d = Math.floor(c1_d * dif);
    
    const im_w = Math.max(c2_x + c2_d / 2, c1_d) + border * 2;
    const im_h = c1_d + border * 2;

    const color1 = "rgba(252, 116, 47, 1)";
    const color1a = "rgba(252, 116, 47, 0.8)";
    const color2 = "rgba(235, 44, 81, 1)";
    const color2a = "rgba(235, 44, 81, 0.8)";
    const white = "rgba(255, 255, 255, 1)";

    const im = new SVG(im_w, im_h);

    if (invert) {
        im.drawCircle('red', im_w - c1_x, c1_y, c1_d / 2, color2a, color2);
        im.drawCircle('orange', im_w - c2_x, c1_y, c2_d / 2, color1a, color1);
    } else {
        im.drawCircle('red', c2_x, c1_y, c2_d / 2, color2a, color2);
        im.drawCircle('orange', c1_x, c1_y, c1_d / 2, color1a, color1);
    }
    
    const margin = 16;
    let start_of_text = (im_h - c2_d) / 2 + margin * 4;
    const end_of_text = c2_d + ((im_h - c2_d) / 2) - margin * 3;
    
    const maxArtistCount = (end_of_text - start_of_text) / margin;
    const curArtistCount = Object.keys(artists3).length;
        
    if (curArtistCount < maxArtistCount) {
        start_of_text = start_of_text + (end_of_text - start_of_text) / 2 - margin * Math.floor(curArtistCount / 2);
    }
    
    let y = start_of_text;

    const intersection_dist = intersectionDistanceByRadius(c1_d / 2, c2_d / 2, distance * zoom);
    
    let x;
    if (invert) {
        x = im_w - (c1_x + intersection_dist);
    } else {
        x = c1_x + intersection_dist;
    }
    
    if (artists3.length === 0) {
        im.drawText(x, im_h - c1_d / 2, "No common artists :(", white);
    }
    
    artists3.some(([key, value]) => {
        im.drawLink(x, y, value.url, `${key} (${value.playcount})`, white);
        y += margin;
        if (y >= end_of_text) {
            return true;
        }
    });

    return im.output();
}

module.exports = { makeImage };