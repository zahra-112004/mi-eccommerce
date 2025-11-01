const { Sequelize } = require('sequelize');

const sequelize = new Sequelize('productdb', 'root', 'root1', {
  host: 'product-db',
  dialect: 'mysql'
});

module.exports = sequelize;
