# app/models/log.py
from app import db
from datetime import datetime

class AdminLog(db.Model):
    __tablename__ = 'admin_logs'
    
    id = db.Column(db.Integer, primary_key=True)
    admin_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    action = db.Column(db.String(50), nullable=False)  # 操作类型
    target_type = db.Column(db.String(20), nullable=False)  # 目标类型（movie/music/game）
    target_id = db.Column(db.Integer)  # 目标ID
    target_name = db.Column(db.String(200))  # 目标名称
    details = db.Column(db.Text)  # 详细信息
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # 关联管理员用户
    admin = db.relationship('User', backref='admin_logs')
    
    def to_dict(self):
        return {
            'id': self.id,
            'admin': self.admin.username,
            'action': self.action,
            'target_type': self.target_type,
            'target_name': self.target_name,
            'details': self.details,
            'created_at': self.created_at.strftime('%Y-%m-%d %H:%M:%S')
        }