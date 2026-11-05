// Regenerates fixtures/test-image.png: a tiny (8x8) but genuinely valid PNG
// (real zlib-deflated IDAT + correct CRC32/chunk framing) for the group
// image upload e2e test. Run with: node client/e2e/fixtures/make-test-image.js
import zlib from 'zlib'
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

const WIDTH = 8
const HEIGHT = 8

function crc32(buf) {
  const table = crc32.table || (crc32.table = (() => {
    const t = []
    for (let n = 0; n < 256; n++) {
      let c = n
      for (let k = 0; k < 8; k++) {
        c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1
      }
      t[n] = c
    }
    return t
  })())
  let crc = 0xffffffff
  for (let i = 0; i < buf.length; i++) {
    crc = table[(crc ^ buf[i]) & 0xff] ^ (crc >>> 8)
  }
  return (crc ^ 0xffffffff) >>> 0
}

function chunk(type, data) {
  const typeBuf = Buffer.from(type, 'ascii')
  const lenBuf = Buffer.alloc(4)
  lenBuf.writeUInt32BE(data.length, 0)
  const crcBuf = Buffer.alloc(4)
  crcBuf.writeUInt32BE(crc32(Buffer.concat([typeBuf, data])), 0)
  return Buffer.concat([lenBuf, typeBuf, data, crcBuf])
}

const signature = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a])

const ihdr = Buffer.alloc(13)
ihdr.writeUInt32BE(WIDTH, 0)
ihdr.writeUInt32BE(HEIGHT, 4)
ihdr[8] = 8 // bit depth
ihdr[9] = 2 // color type: RGB
ihdr[10] = 0
ihdr[11] = 0
ihdr[12] = 0

const rows = []
for (let y = 0; y < HEIGHT; y++) {
  rows.push(Buffer.from([0])) // filter type: none
  for (let x = 0; x < WIDTH; x++) {
    rows.push(Buffer.from([0xd0, 0x40, 0x30])) // solid brick-red pixel
  }
}
const raw = Buffer.concat(rows)
const idatData = zlib.deflateSync(raw)

const png = Buffer.concat([
  signature,
  chunk('IHDR', ihdr),
  chunk('IDAT', idatData),
  chunk('IEND', Buffer.alloc(0)),
])

const outPath = path.join(__dirname, 'test-image.png')
fs.writeFileSync(outPath, png)
console.log('wrote', outPath, png.length, 'bytes')
